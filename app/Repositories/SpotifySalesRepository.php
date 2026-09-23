<?php
namespace FMGlobal\Repositories;
use FMGlobal\Services\Spotify\Rules;
use FMGlobal\Http\HttpException;
final class SpotifySalesRepository {
 public function __construct(private \mysqli $db){}
 public static function period(array $input,?string $today=null):array {
  $today=$today??date('Y-m-d');$end=new \DateTimeImmutable($today);$period=$input['period']??'month';
  if($period==='custom'){$from=Rules::date($input['from']??null);$to=Rules::date($input['to']??null);}
  else{$to=$today;$from=match($period){'today'=>$today,'week'=>$end->modify('-6 days')->format('Y-m-d'),'month'=>$end->modify('-29 days')->format('Y-m-d'),'six'=>$end->modify('-179 days')->format('Y-m-d'),'year'=>$end->modify('-364 days')->format('Y-m-d'),default=>throw new HttpException(422,'Periodo no válido.')};}
  if($from>$to)throw new HttpException(422,'La fecha inicial debe ser anterior o igual a la final.');
  if((new \DateTimeImmutable($from))->diff(new \DateTimeImmutable($to))->days>3660)throw new HttpException(422,'Selecciona un rango de hasta 10 años.');
  return [$from,$to];
 }
 public function report(int $actor,array $input):array {
  $user=(new UserRepository($this->db))->identity($actor);
  if(!$user||(int)$user['estado']!==1||empty((new PermissionRepository($this->db))->effective($actor)['reports.spotify_sales']))throw new HttpException(403,'No tienes permiso para Ventas Spotify.');
  [$from,$to]=self::period($input);$admin=(int)$user['rol_id']===1;$q=Rules::text($input['q']??'',100,false);$metric=$input['metric']??'sale';
  if(!in_array($metric,['sale','renewal','loss','fallen'],true))throw new HttpException(422,'Indicador no válido.');
  $size=(int)($input['size']??20);if(!in_array($size,[10,20,50],true))throw new HttpException(422,'Tamaño no válido.');
  $sql='SELECT seller_id,kind,event_date,COUNT(*) n FROM fm_spotify_sales_events WHERE event_date BETWEEN ? AND ?';$args=[$from,$to];if(!$admin){$sql.=' AND seller_id=?';$args[]=$actor;}$sql.=' GROUP BY seller_id,kind,event_date ORDER BY event_date,seller_id';
  $events=$this->db->execute_query($sql,$args)->fetch_all(MYSQLI_ASSOC);$names=UserNames::all($this->db);$sellers=[];$totals=['sale'=>0,'renewal'=>0,'loss'=>0,'fallen'=>0];
  foreach($events as $event){$id=(int)$event['seller_id'];$name=$names[$id]['display_name']??'Usuario eliminado #'.$id;if($q!==''&&mb_stripos($name,$q)===false)continue;
   if(!isset($sellers[$id]))$sellers[$id]=['id'=>$id,'name'=>$name,'sale'=>0,'renewal'=>0,'loss'=>0,'fallen'=>0];$sellers[$id][$event['kind']]+=(int)$event['n'];$totals[$event['kind']]+=(int)$event['n'];
  }
  uasort($sellers,fn($a,$b)=>strnatcasecmp($a['name'],$b['name']));$total=count($sellers);$pages=max(1,(int)ceil($total/$size));$page=max(1,min($pages,(int)($input['page']??1)));$rows=array_slice(array_values($sellers),($page-1)*$size,$size);$ids=array_column($rows,'id');
  // Totales diarios de todos los vendedores autorizados del filtro, sin paginación.
  $series=[];foreach(['sale'=>'Ventas','renewal'=>'Renovaciones','loss'=>'Pérdidas de clientes','fallen'=>'Cuentas caídas'] as $kind=>$label)$series[$kind]=['id'=>$kind,'name'=>$label,'values'=>[]];
  $byDay=[];foreach($events as $event)if(isset($sellers[(int)$event['seller_id']])){$day=$event['event_date'];$kind=$event['kind'];$byDay[$day][$kind]=($byDay[$day][$kind]??0)+(int)$event['n'];}
  $dates=[];for($d=new \DateTimeImmutable($from);$d<=new \DateTimeImmutable($to);$d=$d->modify('+1 day')){$date=$d->format('Y-m-d');$dates[]=$date;foreach($series as $kind=>&$item)$item['values'][]=$byDay[$date][$kind]??0;unset($item);}
  return compact('rows','total','pages','page','from','to','totals','metric','dates')+['series'=>array_values($series)];
 }
}
