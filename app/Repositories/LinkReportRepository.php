<?php
namespace FMGlobal\Repositories;
use FMGlobal\Services\Links\AccountVault;
final class LinkReportRepository {
 public function __construct(private \mysqli $db,private AccountVault $vault){}
 public function report(?int $actor,int $days,string $search,int $page,int $size):array {
  $where="a.action='generate'";$args=[];
  if($actor!==null){$where.=' AND a.actor_id=?';$args[]=$actor;}
  if($days){$where.=' AND a.created_at>=?';$args[]=(new \DateTimeImmutable('today'))->modify('-'.($days-1).' days')->format('Y-m-d');}
  $records=$this->db->execute_query('SELECT a.id,a.actor_id,a.created_at fecha,a.result,u.usuario,c.credentials FROM fm_link_audit a LEFT JOIN usuarios u ON u.id=a.actor_id LEFT JOIN fm_link_accounts c ON c.id=a.account_id WHERE '.$where.' ORDER BY a.created_at DESC,a.id DESC',$args)->fetch_all(MYSQLI_ASSOC);
  $names=UserNames::all($this->db);$rows=[];$search=mb_strtolower($search);
  foreach($records as $row){$row['correo']=$row['credentials']?explode(':',$this->vault->decrypt($row['credentials']),2)[0]:'';unset($row['credentials']);$row['display_name']=$names[$row['actor_id']]['display_name']??($row['usuario']??'');$row['usuario']=mb_strtolower($row['usuario']??'');$row['generated']=$row['result']==='active';
   if($search!==''&&!str_contains(mb_strtolower($row['correo'].' '.$row['display_name'].' '.$row['usuario'].' '.($row['generated']?'Generado':'No generado')),$search))continue;
   $rows[]=$row;
  }
  $series=[];$start=$days?(new \DateTimeImmutable('today'))->modify('-'.($days-1).' days'):new \DateTimeImmutable($rows?substr($rows[count($rows)-1]['fecha'],0,10):'today');
  for($day=$start;$day<=new \DateTimeImmutable('today');$day=$day->modify('+1 day'))$series[$day->format('Y-m-d')]=[1=>0];
  foreach($rows as $row){$date=substr($row['fecha'],0,10);$series[$date]??=[1=>0];$series[$date][1]++;}
  ksort($series);$total=count($rows);$pages=max(1,(int)ceil($total/$size));$page=max(1,min($pages,$page));
  return ['rows'=>array_slice($rows,($page-1)*$size,$size),'total'=>$total,'page'=>$page,'pages'=>$pages,'series'=>$series];
 }
}
