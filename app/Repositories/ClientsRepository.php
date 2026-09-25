<?php
namespace FMGlobal\Repositories;
use FMGlobal\Services\Spotify\Rules;
use FMGlobal\Http\HttpException;
final class ClientsRepository {
 public function __construct(private \mysqli $db){}
 public function authorize(int $actor):void {
  $user=(new UserRepository($this->db))->identity($actor);
  if(!$user||(int)$user['estado']!==1||empty((new PermissionRepository($this->db))->effective($actor)['clients.manage']))throw new HttpException(403,'No tienes permiso para Clientes.');
 }
 public function listing(int $actor,array $filters):array {
  $this->authorize($actor);$q=Rules::text($filters['q']??'',100,false);$size=(int)($filters['size']??20);if(!in_array($size,[10,20,50],true))throw new HttpException(422,'Tamaño no válido.');
  $args=[$q,$q];$where='LOCATE(?,name)>0 OR LOCATE(?,phone)>0';$total=(int)$this->db->execute_query('SELECT COUNT(*) n FROM fm_clients WHERE '.$where,$args)->fetch_assoc()['n'];$pages=max(1,(int)ceil($total/$size));$page=max(1,min($pages,(int)($filters['page']??1)));
  $rows=$this->db->execute_query('SELECT phone,name FROM fm_clients WHERE '.$where.' ORDER BY name,phone LIMIT '.$size.' OFFSET '.(($page-1)*$size),$args)->fetch_all(MYSQLI_ASSOC);
  return compact('rows','total','page','pages');
 }
 public function information(int $actor,array $filters):array {
  $this->authorize($actor);$phone=Rules::phone($filters['phone']??null);
  $client=$this->db->execute_query('SELECT name,phone FROM fm_clients WHERE phone=?',[$phone])->fetch_assoc();
  if(!$client)throw new HttpException(404,'Cliente no disponible.');
  $administrator=(int)(new UserRepository($this->db))->identity($actor)['rol_id']===1;
  $where='a.client_phone=?';$args=[$phone];
  if(!$administrator){$where.=' AND a.advisor_id=?';$args[]=$actor;}
  $total=(int)$this->db->execute_query('SELECT COUNT(*) n FROM fm_service_assignments a WHERE '.$where,$args)->fetch_assoc()['n'];
  $size=10;$pages=max(1,(int)ceil($total/$size));$page=min($pages,max(1,(int)($filters['page']??1)));$offset=($page-1)*$size;
  // Explicit projection: no passwords, provider emails or provider payment dates.
  $rows=$this->db->execute_query("SELECT s.name service,c.email,b.label beneficiary,a.start_date,a.end_date,DATE(a.closed_at) released_at,
   CASE WHEN a.closed_at IS NOT NULL THEN 'released' WHEN c.state='fallen' THEN 'fallen' WHEN a.end_date<DATE(UTC_TIMESTAMP() - INTERVAL 5 HOUR) THEN 'expired' ELSE 'active' END status,
   CASE WHEN a.closed_at IS NULL THEN DATEDIFF(a.end_date,DATE(UTC_TIMESTAMP() - INTERVAL 5 HOUR)) ELSE NULL END days
   FROM fm_service_assignments a JOIN fm_service_profiles p ON p.id=a.profile_id JOIN fm_service_accounts c ON c.id=p.account_id
   JOIN fm_service_mains m ON m.id=c.main_id JOIN fm_service_types s ON s.id=m.service_id LEFT JOIN fm_client_beneficiaries b ON b.id=a.beneficiary_id
   WHERE $where ORDER BY (a.closed_at IS NULL) DESC,a.start_date DESC,a.id DESC LIMIT $size OFFSET $offset",$args)->fetch_all(MYSQLI_ASSOC);
  return compact('client','rows','total','page','pages');
 }
 public function save(int $actor,array $input):array {
  $this->authorize($actor);$name=Rules::text($input['name']??null,150);$phone=Rules::phone($input['phone']??null);$original=Rules::text($input['original_phone']??'',40,false);
  $this->db->begin_transaction();try{
   (new PermissionRepository($this->db))->lock();$this->authorize($actor);
   if($original!==''){
    $original=Rules::phone($original);$old=$this->db->execute_query('SELECT phone,name FROM fm_clients WHERE phone=? FOR UPDATE',[$original])->fetch_assoc();
    if(!$old||$old['name']!==($input['original_name']??null))throw new HttpException(409,'El cliente cambió. Actualiza la tabla y vuelve a editar.');
    $this->db->execute_query('UPDATE fm_clients SET phone=?,name=? WHERE phone=?',[$phone,$name,$original]);
   }else{$old=null;$this->db->execute_query('INSERT INTO fm_clients(phone,name,created_by,created_at) VALUES(?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$phone,$name,$actor]);}
   $this->db->execute_query('INSERT INTO fm_client_audit(actor_id,before_json,after_json,created_at) VALUES(?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$actor,json_encode($old,JSON_THROW_ON_ERROR),json_encode(compact('phone','name'),JSON_THROW_ON_ERROR)]);
   $this->db->commit();return ['message'=>'Cliente guardado.'];
  }catch(\Throwable $e){$this->db->rollback();if($e instanceof \mysqli_sql_exception&&$e->getCode()===1062)throw new HttpException(409,'El celular ya pertenece a otro cliente.');throw $e;}
 }
 public function importFile(int $actor,string $text):array {
  $this->authorize($actor);
  if(strlen($text)>2097152||!mb_check_encoding($text,'UTF-8'))throw new HttpException(422,'Usa CSV UTF-8 de hasta 2 MB.');
  $text=preg_replace('/^\xEF\xBB\xBF/','',$text);$stream=fopen('php://temp','r+');fwrite($stream,$text);rewind($stream);
  try {
   $first=strtok($text,"\n");$sep=substr_count($first?:'',';')>substr_count($first?:'',',')?';':',';
   if(fgetcsv($stream,0,$sep,'"','')!==['nombre','celular'])throw new HttpException(422,'Las columnas deben coincidir con el CSV modelo: nombre,celular.');
   $rows=[];$line=1;
   while(($values=fgetcsv($stream,0,$sep,'"',''))!==false){$line++;if($values===[null])continue;$rows[]=['line'=>$line,'values'=>$values];if(count($rows)>500)throw new HttpException(422,'Carga hasta 500 filas por archivo.');}
   if(!$rows)throw new HttpException(422,'El CSV no contiene datos.');
  }finally{fclose($stream);}
  $imported=0;$errors=[];
  foreach($rows as $row){$values=$row['values'];try{
   if(count($values)!==2)throw new HttpException(422,'Cantidad de columnas incorrecta.');
   $this->save($actor,['name'=>$values[0],'phone'=>$values[1]]);$imported++;
  }catch(HttpException $e){if(!in_array($e->status,[409,422],true))throw $e;$errors[]=['line'=>$row['line'],'name'=>$values[0]??'','phone'=>$values[1]??'','values'=>$values,'reason'=>$e->getMessage()];}}
  return ['imported'=>$imported,'errors'=>$errors,'message'=>$imported.' clientes importados. '.count($errors).' registros no importados.'];
 }

}
