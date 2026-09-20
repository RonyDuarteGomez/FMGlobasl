<?php
namespace FMGlobal\Repositories;
use FMGlobal\Http\HttpException;
use FMGlobal\Services\Links\AccountVault;
final class LinkAccountRepository {
 public function __construct(private \mysqli $db,private AccountVault $vault){}
 public function actor(int $id):array {
  $user=(new UserRepository($this->db))->identity($id);
  if(!$user||(int)$user['estado']!==1)throw new HttpException(403,'Usuario inactivo o no disponible.');
  if(empty((new PermissionRepository($this->db))->effective($id)['services.links']))throw new HttpException(403,'No tienes acceso a Link Netflix.');
  $user['admin']=(int)$user['rol_id']===1;
  return $user;
 }
 private function admin(int $actor):void {if(!$this->actor($actor)['admin'])throw new HttpException(403,'Acción exclusiva del administrador.');}
 private function transaction(callable $fn):mixed {
  $this->db->begin_transaction();
  try{(new PermissionRepository($this->db))->lock();$result=$fn();$this->db->commit();return $result;}
  catch(\Throwable $e){$this->db->rollback();throw $e;}
 }
 private function audit(int $id,int $actor,string $action,?int $source=null,?int $target=null,?string $result=null):void {
  $this->db->execute_query('INSERT INTO fm_link_audit(account_id,actor_id,action,source_user_id,target_user_id,result,created_at) VALUES(?,?,?,?,?,?,NOW())',[$id,$actor,$action,$source,$target,$result]);
 }
 private function account(int $id,int $actor):array {
  $who=$this->actor($actor);
  $row=$this->db->execute_query('SELECT * FROM fm_link_accounts WHERE id=? FOR UPDATE',[$id])->fetch_assoc();
  if(!$row||(!$who['admin']&&(int)$row['assigned_user_id']!==$actor))throw new HttpException(404,'Cuenta no disponible.');
  return $row;
 }
 private function idle(array $row):void {if($row['generation_until']&&strtotime($row['generation_until'])>time())throw new HttpException(409,'Esta cuenta está generando un enlace. Espera a que termine.');}
 public static function validate(array $data):array {
  foreach(['external_id','secure','credentials'] as $key)if(!isset($data[$key])||!is_string($data[$key])||trim($data[$key])==='')throw new HttpException(422,'ID, secure y correo:contraseña son obligatorios.');
  $id=trim($data['external_id']);$secure=trim($data['secure']);$credentials=trim($data['credentials']);
  foreach([$id,$secure] as $value)if(strlen($value)>8000||preg_match('/[\r\n;\x00]/',$value))throw new HttpException(422,'ID o secure contiene caracteres no admitidos o supera 8000 caracteres.');
  $parts=explode(':',$credentials,2);
  if(count($parts)!==2||!filter_var($parts[0],FILTER_VALIDATE_EMAIL)||$parts[1]===''||strlen($credentials)>1024||preg_match('/[\x00-\x1F]/',$credentials))throw new HttpException(422,'Usa el formato correo:contraseña, con un correo válido.');
  return ['external_id'=>$id,'secure'=>$secure,'credentials'=>strtolower($parts[0]).':'.$parts[1]];
 }
 public function save(int $actor,array $data):int {
  $this->admin($actor);$values=self::validate($data);$id=(int)($data['id']??0);
  return $this->transaction(function()use($actor,$data,$values,$id){
   $this->admin($actor);
   if($id){$row=$this->account($id,$actor);$this->idle($row);if((int)($data['revision']??0)!==(int)$row['revision'])throw new HttpException(409,'La cuenta cambió. Vuelve a abrir la edición.');}
   $args=[$this->vault->encrypt($values['external_id']),$this->vault->encrypt($values['secure']),$this->vault->encrypt($values['credentials']),$this->vault->fingerprint($values['credentials'])];
   try {
    if($id)$this->db->execute_query('UPDATE fm_link_accounts SET external_id=?,secure_value=?,credentials=?,credential_hash=?,generation_token=NULL,generation_until=NULL,revision=revision+1,updated_at=NOW() WHERE id=?',[...$args,$id]);
    else {$this->db->execute_query('INSERT INTO fm_link_accounts(external_id,secure_value,credentials,credential_hash,created_at,updated_at) VALUES(?,?,?,?,NOW(),NOW())',$args);$id=(int)$this->db->insert_id;}
   }catch(\mysqli_sql_exception $e){if($e->getCode()===1062)throw new HttpException(409,'Ya existe ese correo:contraseña.');throw $e;}
   $this->audit($id,$actor,isset($row)?'edit':'create');return $id;
  });
 }
 public function details(int $actor,int $id):array {
  $this->admin($actor);
  return $this->transaction(function()use($actor,$id){$row=$this->account($id,$actor);return ['id'=>$id,'revision'=>(int)$row['revision'],'external_id'=>$this->vault->decrypt($row['external_id']),'secure'=>$this->vault->decrypt($row['secure_value']),'credentials'=>$this->vault->decrypt($row['credentials'])];});
 }
 public function users(int $actor):array {
  $this->admin($actor);$permissions=new PermissionRepository($this->db);$names=UserNames::all($this->db);
  $rows=$this->db->query('SELECT u.id,u.usuario,u.estado,COUNT(a.id) accounts FROM usuarios u LEFT JOIN fm_link_accounts a ON a.assigned_user_id=u.id GROUP BY u.id,u.usuario,u.estado ORDER BY u.usuario')->fetch_all(MYSQLI_ASSOC);
  foreach($rows as &$r){$r['display_name']=$names[$r['id']]['display_name']??$r['usuario'];$r['id']=(int)$r['id'];$r['accounts']=(int)$r['accounts'];$r['technical']=$permissions->isSuperuser($r['id']);$r['eligible']=!$r['technical']&&(int)$r['estado']===1&&!empty($permissions->effective($r['id'])['services.links']);}unset($r);$rows=array_values(array_filter($rows,fn($row)=>$row['eligible']||$row['accounts']>0));usort($rows,fn($a,$b)=>($a['eligible']<=>$b['eligible']) ?: strcasecmp($a['display_name'],$b['display_name']));return $rows;
 }
 public function listing(int $actor,array $filters=[]):array {
  $who=$this->actor($actor);$sql='SELECT a.id,a.credentials,a.status,a.error_type,a.assigned_user_id,a.assigned_at,a.moved_at,a.revision,u.usuario,actor.usuario moved_by FROM fm_link_accounts a LEFT JOIN usuarios u ON u.id=a.assigned_user_id LEFT JOIN usuarios actor ON actor.id=a.moved_by';
  $args=[];if(!$who['admin']){$sql.=' WHERE a.assigned_user_id=?';$args[]=$actor;}
  $rows=$this->db->execute_query($sql.' ORDER BY a.id DESC',$args)->fetch_all(MYSQLI_ASSOC);
  $names=UserNames::all($this->db);$byLogin=array_column($names,'display_name','usuario');
  $ownerAccess=$who['admin']?array_column($this->users($actor),'eligible','id'):[$actor=>true];
  $q=mb_strtolower(trim((string)($filters['q']??'')));$state=(string)($filters['status']??'');$owner=(string)($filters['owner']??'');$result=[];
  foreach($rows as $row){
   $row['display_name']=$names[$row['assigned_user_id']]['display_name']??$row['usuario'];$row['moved_by']=$byLogin[$row['moved_by']]??$row['moved_by'];
   if($state==='no_link' && ($row['status']!=='error'||(int)$row['error_type']!==1))continue;
   if($state==='manual_error' && ($row['status']!=='error'||(int)$row['error_type']!==2))continue;
   if($state!==''&&!in_array($state,['no_link','manual_error'],true)&&$row['status']!==$state)continue;
   if($owner==='none'&&$row['assigned_user_id']!==null)continue;
   if(ctype_digit($owner)&&(int)$owner!==(int)$row['assigned_user_id'])continue;
   $row['credentials']=$this->vault->decrypt($row['credentials']);
   if($q!==''&&!str_contains(mb_strtolower($row['credentials'].' '.($row['display_name']??'').' '.($row['usuario']??'')),$q))continue;
   $row['owner_eligible']=$row['assigned_user_id']===null || !empty($ownerAccess[$row['assigned_user_id']]);$row['usuario']=$row['usuario']===null?null:mb_strtolower($row['usuario']);
   $row['id']=(int)$row['id'];$row['assigned_at']=\FMGlobal\Support\DisplayDate::dateTime($row['assigned_at']);$row['moved_at']=\FMGlobal\Support\DisplayDate::dateTime($row['moved_at']);$result[]=$row;
  }
  usort($result,static function($a,$b){$rank=static fn($r)=>$r['assigned_user_id']===null?0:($r['owner_eligible']?2:1);return ($rank($a)<=>$rank($b)) ?: strcmp($a['display_name']??'',$b['display_name']??'') ?: ($b['id']<=>$a['id']);});
  $total=count($result);$size=in_array((int)($filters['size']??20),[10,20,50],true)?(int)($filters['size']??20):20;$pages=max(1,(int)ceil($total/$size));$page=max(1,min($pages,(int)($filters['page']??1)));
  return ['rows'=>array_slice($result,($page-1)*$size,$size),'total'=>$total,'page'=>$page,'pages'=>$pages];
 }
 public function summary(int $actor):array {
  $who=$this->actor($actor);
  $sql="SELECT COUNT(*) total,COALESCE(SUM(status='active'),0) active,COALESCE(SUM(status='error'),0) errors,COALESCE(SUM(assigned_user_id IS NULL),0) unassigned FROM fm_link_accounts";
  $s=$this->db->execute_query($sql.($who['admin']?'':' WHERE assigned_user_id=?'),$who['admin']?[]:[$actor])->fetch_assoc();
  $s=array_map('intval',$s);$s['admin']=$who['admin'];$s['users']=$who['admin']?array_values(array_filter($this->users($actor),fn($u)=>$u['accounts']>0)):[];
  $s=array_merge($s,$this->consultations($actor));
  $s['orphaned']=array_sum(array_map(fn($u)=>$u['eligible']?0:$u['accounts'],$s['users']));return $s;
 }
 public function consultations(int $actor):array {
  $who=(new UserRepository($this->db))->identity($actor);
  $permissions=(new PermissionRepository($this->db))->effective($actor);
  if(!$who || (int)$who['estado']!==1 || (empty($permissions['services.links']) && empty($permissions['reports.links'])))throw new HttpException(403,'No tienes acceso a consultas link.');
  $who['admin']=(int)$who['rol_id']===1;$s=[];
  $start=(new \DateTimeImmutable('today'))->modify('-29 days')->format('Y-m-d');
  $where="a.action='generate' AND a.created_at>=?";$args=[$start];
  if(!$who['admin']){$where.=' AND a.actor_id=?';$args[]=$actor;}
  $s['generators']=$this->db->execute_query("SELECT a.actor_id,COALESCE(u.usuario,'Sin usuario') usuario,COUNT(*) total FROM fm_link_audit a LEFT JOIN usuarios u ON u.id=a.actor_id WHERE ".$where." GROUP BY a.actor_id,u.usuario ORDER BY total DESC,usuario",$args)->fetch_all(MYSQLI_ASSOC);
  $daily=$this->db->execute_query('SELECT DATE(a.created_at) day,COUNT(*) total FROM fm_link_audit a WHERE '.$where.' GROUP BY DATE(a.created_at)',$args)->fetch_all(MYSQLI_ASSOC);
  $counts=array_column($daily,'total','day');$s['generationSeries']=[];
  for($i=29;$i>=0;$i--){$date=(new \DateTimeImmutable('today'))->modify("-$i days")->format('Y-m-d');$s['generationSeries'][$date]=(int)($counts[$date]??0);}
  $names=UserNames::all($this->db);foreach($s['generators'] as &$generator)$generator['display_name']=$names[$generator['actor_id']]['display_name']??$generator['usuario'];unset($generator);
  $s['generated']=array_sum(array_column($s['generators'],'total'));
  return $s;
 }
 private function moveSelection(int $actor,array $data,bool $apply):array {
  return $this->transaction(function()use($actor,$data,$apply){
   $this->admin($actor);$operation=$data['operation']??'';
   if(!in_array($operation,['assign','release','transfer'],true))throw new HttpException(422,'Movimiento no válido.');
   $source=(int)($data['source']??0);$target=$operation==='release'?null:(int)($data['target']??0);$id=(int)($data['account_id']??0);
   if($target!==null && !(!$apply && !empty($data['availability']))){if((new PermissionRepository($this->db))->isSuperuser($target))throw new HttpException(422,'El usuario admin es de TI y no puede recibir cuentas operativas.');$targetUser=(new UserRepository($this->db))->identity($target);if(!$targetUser||(int)$targetUser['estado']!==1||empty((new PermissionRepository($this->db))->effective($target)['services.links']))throw new HttpException(422,'El destino debe estar activo y tener permiso de Link Netflix.');}
   if($operation!=='assign'&&$source<1)throw new HttpException(422,'Selecciona el usuario origen.');
   if($operation==='transfer'&&$source===$target)throw new HttpException(422,'Selecciona un destino diferente.');
   $where=$operation==='assign'?'assigned_user_id IS NULL':'assigned_user_id=?';$args=$operation==='assign'?[]:[$source];
   if($id){$where.=' AND id=?';$args[]=$id;}
   if(!$id && empty($data['include_error']))$where.=" AND status='active'";
   $where.=' AND (generation_until IS NULL OR generation_until<=NOW())';
   $rows=$this->db->execute_query('SELECT id,assigned_user_id,revision,status,error_type FROM fm_link_accounts WHERE '.$where.' ORDER BY id FOR UPDATE',$args)->fetch_all(MYSQLI_ASSOC);
   $available=count($rows);$all=($data['quantity']??'')==='all';$quantity=$id?1:($all?$available:filter_var($data['quantity']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>100000]]));
   if($quantity===false||$quantity===null)throw new HttpException(422,'Indica una cantidad válida o Todas.');
   $fingerprint=hash('sha256',json_encode([$operation,$source,$target,$quantity,$rows]));
   $assignedTotal=$source?$this->db->execute_query('SELECT COUNT(*) total FROM fm_link_accounts WHERE assigned_user_id=?',[$source])->fetch_assoc()['total']:0;
   $preview=['assigned'=>(int)$assignedTotal,'available'=>$available,'requested'=>$quantity,'count'=>min($quantity,$available),'shortage'=>$quantity>$available,'fingerprint'=>$fingerprint];
   if($id && $rows){$preview['status']=$rows[0]['status'];$preview['error_type']=(int)$rows[0]['error_type'];}
   if(!$apply)return $preview;
   if(!isset($data['fingerprint'])||!hash_equals($fingerprint,(string)$data['fingerprint']))throw new HttpException(409,'La disponibilidad cambió. Revisa y confirma nuevamente.');
   if(!$available)throw new HttpException(422,'No hay cuentas disponibles para esta operación.');
   shuffle($rows);$chosen=array_slice($rows,0,min($quantity,$available));
   foreach($chosen as $row){
    $this->db->execute_query('UPDATE fm_link_accounts SET assigned_user_id=?,assigned_at=IF(? IS NULL,NULL,NOW()),moved_at=NOW(),moved_by=?,generation_token=NULL,generation_until=NULL,revision=revision+1,updated_at=NOW() WHERE id=?',[$target,$target,$actor,$row['id']]);
    $this->audit((int)$row['id'],$actor,$operation,$row['assigned_user_id']===null?null:(int)$row['assigned_user_id'],$target);
   }
   return $preview+['message'=>count($chosen).' cuentas actualizadas.'];
  });
 }
 public function preview(int $actor,array $data):array{return $this->moveSelection($actor,$data,false);}
 public function move(int $actor,array $data):array{return $this->moveSelection($actor,$data,true);}
 public function markError(int $actor,int $id,int $revision):void {
  $this->transaction(function()use($actor,$id,$revision){$this->admin($actor);$row=$this->account($id,$actor);$this->idle($row);if((int)$row['revision']!==$revision)throw new HttpException(409,'La cuenta cambió. Actualiza la tabla.');$this->db->execute_query("UPDATE fm_link_accounts SET status='error',error_type=2,generation_token=NULL,generation_until=NULL,revision=revision+1,updated_at=NOW() WHERE id=?",[$id]);$this->audit($id,$actor,'manual_error',null,null,'error');});
 }
 public function beginGeneration(int $actor,int $id):array {
  return $this->transaction(function()use($actor,$id){$row=$this->account($id,$actor);$this->idle($row);$token=bin2hex(random_bytes(16));$this->db->execute_query('UPDATE fm_link_accounts SET generation_token=?,generation_until=DATE_ADD(NOW(),INTERVAL 90 SECOND) WHERE id=?',[$token,$id]);return ['token'=>$token,'external_id'=>$this->vault->decrypt($row['external_id']),'secure'=>$this->vault->decrypt($row['secure_value'])];});
 }
 public function finishGeneration(int $actor,int $id,string $token,bool $ok):void {
  $this->transaction(function()use($actor,$id,$token,$ok){
   $row=$this->db->execute_query('SELECT generation_token FROM fm_link_accounts WHERE id=? FOR UPDATE',[$id])->fetch_assoc();
   if(!$row||!hash_equals((string)$row['generation_token'],$token))throw new HttpException(409,'La generación fue reemplazada. Vuelve a intentar.');
   $this->db->execute_query('UPDATE fm_link_accounts SET status=?,error_type=?,generation_token=NULL,generation_until=NULL,revision=revision+1,updated_at=NOW() WHERE id=?',[$ok?'active':'error',$ok?0:1,$id]);
   $this->audit($id,$actor,'generate',null,null,$ok?'active':'error');
   if($ok){(new UsageRepository($this->db))->register('',1,$actor,7);}
  });
 }
}
