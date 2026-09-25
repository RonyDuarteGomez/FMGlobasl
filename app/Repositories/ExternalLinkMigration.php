<?php
namespace FMGlobal\Repositories;
final class ExternalLinkMigration {
 public static function apply(\mysqli $db):void {
  foreach([
   "CREATE TABLE IF NOT EXISTS fm_external_rules(target_type VARCHAR(8) NOT NULL,target_id INT NOT NULL,settings_json TEXT NOT NULL,revision INT NOT NULL DEFAULT 1,PRIMARY KEY(target_type,target_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
   "CREATE TABLE IF NOT EXISTS fm_external_browsers(id BIGINT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,token_hash CHAR(64) NOT NULL,state VARCHAR(12) NOT NULL DEFAULT 'pending',label VARCHAR(200) NOT NULL,session_hash CHAR(64) NULL,created_at DATETIME NOT NULL,approved_by INT NULL,UNIQUE(user_id,token_hash)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
   "CREATE TABLE IF NOT EXISTS fm_external_attempts(id BIGINT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,request_key CHAR(32) NOT NULL,success TINYINT NOT NULL DEFAULT 0,finished TINYINT NOT NULL DEFAULT 0,created_at DATETIME NOT NULL,UNIQUE(user_id,request_key),INDEX(user_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
   "CREATE TABLE IF NOT EXISTS fm_external_audit(id BIGINT AUTO_INCREMENT PRIMARY KEY,actor_id INT NOT NULL,action VARCHAR(30) NOT NULL,target_type VARCHAR(8) NOT NULL,target_id INT NOT NULL,details_json TEXT NOT NULL,created_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
  ] as $sql)$db->query($sql);
  $db->begin_transaction();try{(new PermissionRepository($db))->lock();
   if(!$db->query("SELECT name FROM fm_migrations WHERE name='009_external_links'")->num_rows){
    $role=$db->query("SELECT rol_id FROM rol WHERE rol_nombre='Externo'")->fetch_assoc();
    if(!$role){$id=(int)$db->query('SELECT COALESCE(MAX(rol_id),0)+1 id FROM rol')->fetch_assoc()['id'];$db->execute_query('INSERT INTO rol(rol_id,rol_nombre) VALUES(?,?)',[$id,'Externo']);}else $id=(int)$role['rol_id'];
    foreach(['services.external_links','external.restrictions','reports.external_links'] as $code){[$section,$label]=\FMGlobal\Security\PermissionCatalog::ITEMS[$code];$db->execute_query('INSERT IGNORE INTO fm_permissions VALUES(?,?,?)',[$code,$section,$label]);$db->execute_query('INSERT IGNORE INTO fm_role_permissions VALUES(1,?,1)',[$code]);}
    $db->execute_query("INSERT IGNORE INTO fm_role_permissions VALUES(?,'services.external_links',1)",[$id]);
    $db->query("INSERT INTO fm_migrations VALUES('009_external_links',NOW())");(new PermissionRepository($db))->bump();
   }$db->commit();
  }catch(\Throwable $e){$db->rollback();throw $e;}
 }
}
