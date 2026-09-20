<?php
namespace FMGlobal\Repositories;

final class UsernameMigration
{
    /** Dry-run validates final names using the same collation as usuarios.usuario. */
    public static function plan(\mysqli $db): array
    {
        $column=$db->query("SHOW FULL COLUMNS FROM usuarios LIKE 'usuario'")->fetch_assoc();
        $collation=$column['Collation'];
        if (!preg_match('/^[a-z0-9_]+$/i',$collation)) throw new \RuntimeException('Collation no compatible.');
        $charset=explode('_',$collation)[0];
        $rows=$db->query('SELECT id,usuario FROM usuarios ORDER BY id')->fetch_all(MYSQLI_ASSOC);
        $changes=[];
        foreach ($rows as &$row) {
            $row['target']=preg_replace('/\s+/u','_',$row['usuario']);
            if ($row['target']===null) throw new \RuntimeException('Nombre de usuario no válido.');
            if ($row['target']!==$row['usuario']) $changes[]=$row;
        }
        unset($row);
        foreach ($changes as $change) foreach ($rows as $row) {
            if ($change['id']===$row['id']) continue;
            $same=$db->execute_query("SELECT CONVERT(? USING $charset) COLLATE $collation = CONVERT(? USING $charset) COLLATE $collation same_name",[$change['target'],$row['target']])->fetch_assoc()['same_name'];
            if ($same) throw new \RuntimeException('Normalizar usuarios produciría una duplicidad entre IDs '.$change['id'].' y '.$row['id'].'. Resolver antes de aplicar.');
        }
        return $changes;
    }

    public static function apply(\mysqli $db): int
    {
        if (!UsageUserMigration::hasColumn($db) || !$db->query("SELECT name FROM fm_migrations WHERE name='004_usage_user_id'")->num_rows) {
            throw new \RuntimeException('Migrar primero los IDs del historial.');
        }
        $db->begin_transaction();
        try {
            $db->query('SELECT id FROM usuarios ORDER BY id FOR UPDATE');
            $changes=self::plan($db);
            foreach ($changes as $row) $db->execute_query('UPDATE usuarios SET usuario=? WHERE id=?',[$row['target'],$row['id']]);
            $db->commit();
            return count($changes);
        } catch (\Throwable $e) {$db->rollback();throw $e;}
    }
}
