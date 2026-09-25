<?php
namespace FMGlobal\Repositories;
final class SpotifyEvolutionMigration {
 public static function apply(\mysqli $db):void {
  $db->query("CREATE TABLE IF NOT EXISTS fm_client_beneficiaries(id INT AUTO_INCREMENT PRIMARY KEY,client_phone VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,label VARCHAR(80) NOT NULL,UNIQUE(client_phone,label),FOREIGN KEY(client_phone) REFERENCES fm_clients(phone) ON UPDATE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $db->query("CREATE TABLE IF NOT EXISTS fm_notifications(id BIGINT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,account_id INT NOT NULL,message VARCHAR(500) NOT NULL,created_at DATETIME NOT NULL,read_at DATETIME NULL,INDEX(user_id,read_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  if(!$db->query("SHOW COLUMNS FROM fm_service_assignments LIKE 'beneficiary_id'")->num_rows)$db->query('ALTER TABLE fm_service_assignments ADD beneficiary_id INT NULL, ADD FOREIGN KEY(beneficiary_id) REFERENCES fm_client_beneficiaries(id)');
  if($db->query("SHOW COLUMNS FROM fm_service_assignments LIKE 'client_phone'")->fetch_assoc()['Null']==='NO')$db->query('ALTER TABLE fm_service_assignments MODIFY client_phone VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NULL');
  foreach(['fm_service_assignments','fm_service_renewals'] as $table)if(!$db->query("SHOW COLUMNS FROM $table LIKE 'months'")->num_rows)$db->query("ALTER TABLE $table ADD months INT NOT NULL DEFAULT 1");
  $db->begin_transaction();try{(new PermissionRepository($db))->lock();
   if(!$db->query("SELECT name FROM fm_migrations WHERE name='008_spotify_evolution'")->num_rows){
    // Restaurar solo la última asignación cerrada por caída en cuentas aún caídas.
    $db->query("UPDATE fm_service_profiles p JOIN fm_service_accounts c ON c.id=p.account_id JOIN fm_service_assignments a ON a.profile_id=p.id SET p.current_assignment_id=a.id,a.closed_at=NULL,a.close_reason=NULL,a.revision=a.revision+1 WHERE c.state='fallen' AND p.current_assignment_id IS NULL AND a.close_reason='fallen' AND a.id=(SELECT latest.id FROM (SELECT profile_id,MAX(id) id FROM fm_service_assignments GROUP BY profile_id) latest WHERE latest.profile_id=p.id)");
    $db->query("INSERT INTO fm_migrations VALUES('008_spotify_evolution',NOW())");
   }$db->commit();
  }catch(\Throwable $e){$db->rollback();throw $e;}
 }
}
