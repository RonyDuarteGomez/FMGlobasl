<?php
namespace FMGlobal\Repositories;
final class SpotifySalesMigration {
 public static function apply(\mysqli $db):void {
  $db->query("CREATE TABLE IF NOT EXISTS fm_client_audit(id BIGINT AUTO_INCREMENT PRIMARY KEY,actor_id INT NOT NULL,before_json TEXT NULL,after_json TEXT NOT NULL,created_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $db->query("CREATE TABLE IF NOT EXISTS fm_spotify_sales_events(id BIGINT AUTO_INCREMENT PRIMARY KEY,audit_id BIGINT NOT NULL,seller_id INT NOT NULL,actor_id INT NOT NULL,kind VARCHAR(16) NOT NULL,event_date DATE NOT NULL,UNIQUE(audit_id,seller_id,kind),INDEX(event_date,seller_id,kind)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $db->begin_transaction();try{
   (new PermissionRepository($db))->lock();
   if(!$db->query("SELECT name FROM fm_migrations WHERE name='007_spotify_sales_history_v2'")->num_rows){
    // Reconstruir responsables solo desde asignaciones y movimientos explícitos.
    $events=$db->query("SELECT * FROM fm_service_audit WHERE action IN ('obtain','renew','release','fall','transfer','import_account') ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    $assignments=array_column($db->query('SELECT a.*,p.account_id FROM fm_service_assignments a JOIN fm_service_profiles p ON p.id=a.profile_id')->fetch_all(MYSQLI_ASSOC),null,'id');
    $active=[];
    $sellerAt=function(array $assignment,int $auditId)use($db):?int{
     $later=$db->execute_query("SELECT details_json FROM fm_service_audit WHERE action='transfer' AND entity_id=? AND id>? ORDER BY id LIMIT 1",[$assignment['id'],$auditId])->fetch_assoc();
     $seller=$later?(json_decode($later['details_json'],true)['from']??null):$assignment['advisor_id'];return $seller?(int)$seller:null;
    };
    foreach($events as $event){
     $details=json_decode($event['details_json']??'{}',true)?:[];$action=$event['action'];$entity=(int)$event['entity_id'];$assignment=$assignments[$entity]??null;
     if($action==='import_account'){
      if(!empty($details['assigned']))foreach($assignments as $a)if((int)$a['account_id']===$entity&&$a['created_at']<=$event['created_at']&&($a['closed_at']===null||$a['closed_at']>=$event['created_at']))$active[$entity][(int)$a['id']]=$sellerAt($a,(int)$event['id']);
      continue;
     }
     if($action==='transfer'){
      if($assignment&&isset($active[$assignment['account_id']][$entity]))$active[$assignment['account_id']][$entity]=(int)($details['to']??$assignment['advisor_id']);continue;
     }
     if($action==='obtain'&&$assignment)$active[$assignment['account_id']][$entity]=(int)($details['advisor_id']??$sellerAt($assignment,(int)$event['id']));
     if(isset($details['seller_ids']))$sellerIds=$details['seller_ids'];
     elseif($action==='fall')$sellerIds=array_values($active[$entity]??[]);
     elseif($assignment)$sellerIds=[($action==='obtain'?$details['advisor_id']??null:null)??$sellerAt($assignment,(int)$event['id'])];
     else $sellerIds=[];
     foreach(array_unique($sellerIds) as $seller)if($seller>0)$db->execute_query('INSERT IGNORE INTO fm_spotify_sales_events(audit_id,seller_id,actor_id,kind,event_date) VALUES(?,?,?,?,?)',[$event['id'],$seller,$event['actor_id'],['obtain'=>'sale','renew'=>'renewal','release'=>'loss','fall'=>'fallen'][$action],substr($event['created_at'],0,10)]);
     if($action==='fall')unset($active[$entity]);
     if($action==='release'&&$assignment)unset($active[$assignment['account_id']][$entity]);
    }
    $db->query("INSERT INTO fm_migrations(name,applied_at) VALUES('007_spotify_sales_history_v2',NOW())");
   }$db->commit();
  }catch(\Throwable $e){$db->rollback();throw $e;}
 }
}
