<?php
namespace FMGlobal\Repositories;
use FMGlobal\Security\PermissionCatalog;
final class PermissionMigration
{
    public static function apply(\mysqli $db): void
    {
        // DDL separado de la transacción de datos: MySQL confirma DDL implícitamente.
        foreach ([
            "CREATE TABLE IF NOT EXISTS fm_permissions (code VARCHAR(64) PRIMARY KEY, section_name VARCHAR(80) NOT NULL, label VARCHAR(100) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS fm_role_permissions (role_id INT NOT NULL, permission_code VARCHAR(64) NOT NULL, allowed TINYINT NOT NULL, PRIMARY KEY(role_id,permission_code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS fm_user_permissions (user_id INT NOT NULL, permission_code VARCHAR(64) NOT NULL, allowed TINYINT NOT NULL, PRIMARY KEY(user_id,permission_code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS fm_permission_audit (id BIGINT AUTO_INCREMENT PRIMARY KEY, actor_id INT NOT NULL, target_type VARCHAR(10) NOT NULL, target_id INT NOT NULL, before_json LONGTEXT NOT NULL, after_json LONGTEXT NOT NULL, created_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS fm_permission_lock (id INT PRIMARY KEY, revision INT NOT NULL DEFAULT 0) ENGINE=InnoDB",
            "CREATE TABLE IF NOT EXISTS fm_migrations (name VARCHAR(100) PRIMARY KEY, applied_at DATETIME NOT NULL) ENGINE=InnoDB",
        ] as $sql) $db->query($sql);
        $db->query('INSERT IGNORE INTO fm_permission_lock(id,revision) VALUES(1,0)');
        $db->begin_transaction();
        try {
            $db->query('SELECT revision FROM fm_permission_lock WHERE id=1 FOR UPDATE');
            if (!$db->query("SELECT name FROM fm_migrations WHERE name='001_permissions'")->num_rows) {
                foreach (PermissionCatalog::ITEMS as $code=>[$section,$label]) {
                    $db->execute_query('INSERT IGNORE INTO fm_permissions(code,section_name,label) VALUES(?,?,?)',[$code,$section,$label]);
                }
                foreach ([1,2,3] as $role) foreach (PermissionCatalog::defaults($role) as $code) {
                    $db->execute_query('INSERT IGNORE INTO fm_role_permissions(role_id,permission_code,allowed) VALUES(?,?,1)',[$role,$code]);
                }
                $db->query("INSERT INTO fm_migrations(name,applied_at) VALUES('001_permissions',NOW())");
            }
            // Actualiza solo la categoria; conserva las asignaciones de perfiles y usuarios.
            $db->execute_query('UPDATE fm_permissions SET section_name=? WHERE code=?',[PermissionCatalog::ITEMS['services.links'][0],'services.links']);
            if (!$db->query("SELECT name FROM fm_migrations WHERE name='002_support_report'")->num_rows) {
                $db->execute_query('INSERT IGNORE INTO fm_permissions(code,section_name,label) VALUES(?,?,?)',['reports.support','Reportes','Consultas soporte']);
                $db->query("INSERT INTO fm_role_permissions(role_id,permission_code,allowed) SELECT role_id,'reports.support',allowed FROM fm_role_permissions WHERE permission_code='services.support' ON DUPLICATE KEY UPDATE allowed=VALUES(allowed)");
                $db->query("INSERT INTO fm_user_permissions(user_id,permission_code,allowed) SELECT user_id,'reports.support',allowed FROM fm_user_permissions WHERE permission_code='services.support' ON DUPLICATE KEY UPDATE allowed=VALUES(allowed)");
                $db->query("INSERT INTO fm_migrations(name,applied_at) VALUES('002_support_report',NOW())");
            }
            if (!$db->query("SELECT name FROM fm_migrations WHERE name='003_link_report'")->num_rows) {
                $db->query("INSERT IGNORE INTO fm_permissions(code,section_name,label) VALUES('reports.links','Reportes','Consultas link')");
                $db->query("INSERT IGNORE INTO fm_role_permissions(role_id,permission_code,allowed) VALUES(1,'reports.links',1)");
                $db->query("INSERT INTO fm_migrations(name,applied_at) VALUES('003_link_report',NOW())");
                $db->query('UPDATE fm_permission_lock SET revision=revision+1 WHERE id=1');
            }
            $db->commit();
        } catch (\Throwable $e) { $db->rollback(); throw $e; }
    }
}
