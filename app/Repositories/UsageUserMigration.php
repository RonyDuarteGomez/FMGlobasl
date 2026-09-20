<?php
namespace FMGlobal\Repositories;

/** Additive migration: preserve the original username as a historical snapshot. */
final class UsageUserMigration
{
    private const NAME = '004_usage_user_id';

    public static function hasColumn(\mysqli $db): bool
    {
        return $db->query("SHOW COLUMNS FROM uso_servicio LIKE 'usuario_id'")->num_rows > 0;
    }

    public static function report(\mysqli $db): array
    {
        $id = self::hasColumn($db) ? 'log.usuario_id' : 'NULL';
        return $db->query("SELECT COUNT(*) total,
            COALESCE(SUM($id IS NOT NULL),0) linked,
            COALESCE(SUM($id IS NULL AND (log.usuario IS NULL OR TRIM(log.usuario)='')),0) anonymous,
            COALESCE(SUM($id IS NULL AND TRIM(COALESCE(log.usuario,''))<>''),0) unresolved
            FROM uso_servicio log")->fetch_assoc();
    }

    public static function apply(\mysqli $db): array
    {
        // Named lock also covers DDL, which implicitly commits in MySQL.
        $lock = 'fm_usage_'.substr(hash('sha256',$db->query('SELECT DATABASE() db')->fetch_assoc()['db']),0,40);
        if ((int)$db->execute_query('SELECT GET_LOCK(?,10) acquired',[$lock])->fetch_assoc()['acquired'] !== 1) {
            throw new \RuntimeException('Otra migración de consultas está en ejecución.');
        }
        try {
            if (!self::hasColumn($db)) {
                $type = $db->query("SHOW COLUMNS FROM usuarios LIKE 'id'")->fetch_assoc()['Type'];
                if (!preg_match('/^(?:tinyint|smallint|mediumint|int|bigint)(?:\(\d+\))?(?: unsigned)?$/i',$type)) {
                    throw new \RuntimeException('Tipo de ID no compatible; revisar esquema antes de migrar.');
                }
                $db->query("ALTER TABLE uso_servicio ADD COLUMN usuario_id $type NULL AFTER usuario");
            }
            if (!$db->query("SHOW INDEX FROM uso_servicio WHERE Key_name='idx_usage_user_operation_date'")->num_rows) {
                $db->query('ALTER TABLE uso_servicio ADD INDEX idx_usage_user_operation_date (usuario_id,streaming,fecha)');
            }
            $db->query('CREATE TABLE IF NOT EXISTS fm_migrations (name VARCHAR(100) PRIMARY KEY,applied_at DATETIME NOT NULL) ENGINE=InnoDB');
            $db->begin_transaction();
            try {
                if (!$db->execute_query('SELECT name FROM fm_migrations WHERE name=?',[self::NAME])->num_rows) {
                    // Exact, unique match only. Never infer identity from names or modified logins.
                    $db->query("UPDATE uso_servicio log INNER JOIN
                        (SELECT BINARY usuario login,MIN(id) user_id FROM usuarios GROUP BY BINARY usuario HAVING COUNT(*)=1) matches
                        ON BINARY log.usuario=matches.login
                        SET log.usuario_id=matches.user_id
                        WHERE log.usuario_id IS NULL AND TRIM(COALESCE(log.usuario,''))<>''");
                    $db->execute_query('INSERT INTO fm_migrations(name,applied_at) VALUES(?,NOW())',[self::NAME]);
                }
                $db->commit();
            } catch (\Throwable $e) { $db->rollback(); throw $e; }
            // Marker prevents a later reused username from claiming unresolved old activity.
            return self::report($db);
        } finally {
            $db->execute_query('SELECT RELEASE_LOCK(?)',[$lock]);
        }
    }
}
