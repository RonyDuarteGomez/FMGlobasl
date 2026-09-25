<?php
namespace FMGlobal\Repositories;
final class SpotifyIdentityMigration {
 public static function apply(\mysqli $db):void {
  // Add the replacement constraints before removing the old, stricter indexes.
  foreach([
   ['fm_service_mains','uq_spotify_main_payment','service_id,email,payment_email',['service_id','email']],
   ['fm_service_accounts','uq_spotify_main_account','main_id,email',['email']],
  ] as [$table,$name,$columns,$oldColumns]) {
   $indexes=[];foreach($db->query("SHOW INDEX FROM `$table`")->fetch_all(MYSQLI_ASSOC) as $index)if(!(int)$index['Non_unique'])$indexes[$index['Key_name']][(int)$index['Seq_in_index']]=$index['Column_name'];
   if(!isset($indexes[$name]))$db->query("ALTER TABLE `$table` ADD UNIQUE KEY `$name` ($columns)");
   foreach($indexes as $key=>$parts){ksort($parts);if(array_values($parts)===$oldColumns){$safe=str_replace('`','``',$key);$db->query("ALTER TABLE `$table` DROP INDEX `$safe`");}}
  }
  $db->query("INSERT IGNORE INTO fm_migrations(name,applied_at) VALUES('010_spotify_account_identity',NOW())");
 }
}
