<?php
namespace FMGlobal\Repositories;
use FMGlobal\Services\Links\AccountVault;
final class LinkCredentialMigration {
 public static function apply(\mysqli $db,AccountVault $vault):int {
  $db->begin_transaction();
  try {
   (new PermissionRepository($db))->lock();
   $rows=$db->query('SELECT id,credentials FROM fm_link_accounts FOR UPDATE')->fetch_all(MYSQLI_ASSOC);$count=0;
   foreach($rows as $row){
    $plain=$vault->decrypt($row['credentials']);
    $current=explode(':',$plain,2);
    if(count($current)===2&&filter_var($current[0],FILTER_VALIDATE_EMAIL))continue;
    $legacy=explode('/',$plain,2);
    if(count($legacy)!==2||!filter_var($legacy[0],FILTER_VALIDATE_EMAIL))throw new \RuntimeException('Formato de cuenta no reconocido; migración cancelada.');
    $value=strtolower($legacy[0]).':'.$legacy[1];
    $db->execute_query('UPDATE fm_link_accounts SET credentials=?,credential_hash=?,revision=revision+1,updated_at=NOW() WHERE id=?',[$vault->encrypt($value),$vault->fingerprint($value),$row['id']]);$count++;
   }
   $db->commit();return $count;
  }catch(\Throwable $e){$db->rollback();throw $e;}
 }
}
