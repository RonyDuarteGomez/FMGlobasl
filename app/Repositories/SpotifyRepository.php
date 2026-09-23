<?php
namespace FMGlobal\Repositories;
use FMGlobal\Http\HttpException;
use FMGlobal\Services\Links\AccountVault;
use FMGlobal\Services\Spotify\Rules;
// Fechas de operación y cortes diarios en Lima (UTC-05), independientes del reloj de sesión MySQL.
final class SpotifyRepository {
 public function __construct(private \mysqli $db,private AccountVault $vault){}
 public function actor(int $id):array {
  $user=(new UserRepository($this->db))->identity($id);
  if(!$user||(int)$user['estado']!==1||empty((new PermissionRepository($this->db))->effective($id)['services.spotify']))throw new HttpException(403,'No tienes acceso a Spotify.');
  $user['admin']=(int)$user['rol_id']===1;return $user;
 }
 private function admin(int $id):void {if(!$this->actor($id)['admin'])throw new HttpException(403,'Acción exclusiva del administrador.');}
 private function audit(int $actor,string $action,int $id,array $details=[]):void {$this->db->execute_query('INSERT INTO fm_service_audit(actor_id,action,entity_id,details_json,created_at) VALUES(?,?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$actor,$action,$id,json_encode($details,JSON_THROW_ON_ERROR)]);
  $auditId=(int)$this->db->insert_id;$kind=['obtain'=>'sale','renew'=>'renewal','release'=>'loss','fall'=>'fallen'][$action]??null;
  if($kind)foreach(array_unique($details['seller_ids']??[]) as $seller)$this->db->execute_query('INSERT INTO fm_spotify_sales_events(audit_id,seller_id,actor_id,kind,event_date) VALUES(?,?,?,?,DATE(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$auditId,$seller,$actor,$kind]);
 }
 public function execute(int $actor,string $action,array $input):array {
  $this->actor($actor);$key=$input['request_key']??'';
  if(!is_string($key)||!preg_match('/^[a-f0-9]{32}$/D',$key))throw new HttpException(422,'Identificador de operación no válido.');
  $hash=$this->vault->fingerprint(json_encode([$action,$input],JSON_THROW_ON_ERROR));
  $this->db->begin_transaction();
  try {
   (new PermissionRepository($this->db))->lock();$this->actor($actor);
   if(in_array($action,['save','rehabilitate','transfer','import','pay','pay_bulk'],true))$this->admin($actor);
   $old=$this->db->execute_query('SELECT action,input_hash,result_json FROM fm_service_commands WHERE actor_id=? AND request_key=?',[$actor,$key])->fetch_assoc();
   if($old){if($old['action']!==$action||!hash_equals($old['input_hash'],$hash))throw new HttpException(409,'La operación cambió. Abre de nuevo el formulario.');$this->db->commit();return json_decode($old['result_json'],true,512,JSON_THROW_ON_ERROR);}
   $result=match($action){'pay_bulk'=>$this->payBulk($actor,$input),'pay'=>$this->pay($actor,$input),'import'=>$this->importCsv($actor,$input),'save'=>$this->save($actor,$input),'obtain'=>$this->obtain($actor,$input),'renew'=>$this->renew($actor,$input),'release'=>$this->release($actor,$input),'fall'=>$this->fall($actor,$input),'rehabilitate'=>$this->rehabilitate($actor,$input),'credentials'=>$this->credentials($actor,$input),'transfer'=>$this->transfer($actor,$input),default=>throw new HttpException(422,'Acción no válida.')};
   $this->db->execute_query('INSERT INTO fm_service_commands(actor_id,request_key,action,input_hash,result_json,created_at) VALUES(?,?,?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$actor,$key,$action,$hash,json_encode($result,JSON_THROW_ON_ERROR)]);
   $this->db->commit();return $result;
  }catch(\Throwable $e){$this->db->rollback();if($e instanceof \mysqli_sql_exception&&$e->getCode()===1062)throw new HttpException(409,'El correo o registro ya existe. Revisa los datos.');throw $e;}
 }
 public function importFile(int $actor,string $text,string $key):array {
  $this->admin($actor);
  return $this->execute($actor,'import',['request_key'=>$key,'rows'=>\FMGlobal\Services\Spotify\CsvImport::parse($text)]);
 }
 private function importCsv(int $actor,array $input):array {
  $this->admin($actor);$rows=$input['rows']??[];
  if(!is_array($rows)||!$rows||count($rows)>500)throw new HttpException(422,'Carga entre 1 y 500 filas.');
  $loaded=0;$errors=[];
  foreach($rows as $index=>$r){
   $this->db->query('SAVEPOINT csv_row');
   try{
    if(!is_array($r))throw new HttpException(422,'Fila no válida.');
    foreach(\FMGlobal\Services\Spotify\CsvImport::HEADER as $column)if(!isset($r[$column])||!is_string($r[$column]))throw new HttpException(422,'Faltan columnas.');
    $service=$this->db->execute_query('SELECT * FROM fm_service_types WHERE name=? AND active=1',[$r['servicio']])->fetch_assoc();
    if(!$service)throw new HttpException(422,'Tipo de servicio inexistente o inactivo.');
    $email=Rules::email($r['correo_principal']);$payment=Rules::email($r['correo_pago']);$next=Rules::date($r['proximo_pago']);$secondary=Rules::email($r['correo_secundario']);$password=Rules::password($r['contrasena']);$profile=Rules::text($r['perfil'],80,false)?:'Perfil 1';
    if(!in_array($r['estado_cuenta'],['habilitada','caida'],true))throw new HttpException(422,'Estado permitido: habilitada o caida.');
    if($this->db->execute_query('SELECT id FROM fm_service_accounts WHERE email=?',[$secondary])->fetch_assoc())throw new HttpException(409,'Correo secundario ya registrado.');
    $assigned=false;foreach(['asesor_usuario','cliente_nombre','cliente_celular','inicio_servicio','vencimiento_servicio','ultima_renovacion'] as $c)if($r[$c]!=='')$assigned=true;
    $advisor=null;$renew=null;
    if($assigned){
     if($r['estado_cuenta']==='caida')throw new HttpException(422,'Una cuenta caída no puede tener asignación.');
     $advisor=(new UserRepository($this->db))->forLogin(Rules::text($r['asesor_usuario'],100));
     if(!$advisor||(int)$advisor['estado']!==1||(new PermissionRepository($this->db))->isSuperuser((int)$advisor['id'])||empty((new PermissionRepository($this->db))->effective((int)$advisor['id'])['services.spotify']))throw new HttpException(422,'El asesor no existe, está inactivo o no tiene permiso para Spotify. Admin no recibe cuentas.');
     $phone=Rules::phone($r['cliente_celular']);$name=Rules::text($r['cliente_nombre'],150);$start=Rules::date($r['inicio_servicio']);$end=Rules::date($r['vencimiento_servicio']);
     if($end<=$start)throw new HttpException(422,'El vencimiento debe ser posterior al inicio.');
     if($r['ultima_renovacion']!==''){$renew=Rules::date($r['ultima_renovacion']);if($renew<$start||$renew>date('Y-m-d')||$renew>$end)throw new HttpException(422,'Última renovación fuera del rango permitido.');}
     $client=$this->db->execute_query('SELECT name FROM fm_clients WHERE phone=?',[$phone])->fetch_assoc();
     if($client&&$client['name']!==$name)throw new HttpException(409,'El celular pertenece a un cliente con otro nombre.');
    }
    $main=$this->db->execute_query('SELECT * FROM fm_service_mains WHERE service_id=? AND email=? FOR UPDATE',[$service['id'],$email])->fetch_assoc();
    if($main){
     if($main['payment_email']!==$payment||$main['next_payment']!==$next)throw new HttpException(409,'Los datos de pago difieren de la cuenta principal existente.');
     $mainId=(int)$main['id'];
    }else{$this->db->execute_query('INSERT INTO fm_service_mains(service_id,email,payment_email,next_payment,created_at,updated_at) VALUES(?,?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR),(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$service['id'],$email,$payment,$next]);$mainId=(int)$this->db->insert_id;}
    if((int)$this->db->execute_query('SELECT COUNT(*) n FROM fm_service_accounts WHERE main_id=? AND state<>"deleted"',[$mainId])->fetch_assoc()['n']>=(int)$service['max_accounts'])throw new HttpException(422,'Se supera el límite de secundarias de la cuenta principal.');
    $this->db->execute_query('INSERT INTO fm_service_accounts(main_id,email,password_cipher,state,created_at,updated_at) VALUES(?,?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR),(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$mainId,$secondary,$this->vault->encrypt($password),$r['estado_cuenta']==='caida'?'fallen':'enabled']);$accountId=(int)$this->db->insert_id;
    $this->db->execute_query('INSERT INTO fm_service_profiles(account_id,name) VALUES(?,?)',[$accountId,$profile]);$profileId=(int)$this->db->insert_id;
    if($assigned){
     if(!$client)$this->db->execute_query('INSERT INTO fm_clients(phone,name,created_by,created_at) VALUES(?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$phone,$name,$actor]);
     $this->db->execute_query('INSERT INTO fm_service_assignments(profile_id,client_phone,advisor_id,start_date,end_date,last_renewed_at,created_by,created_at) VALUES(?,?,?,?,?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$profileId,$phone,$advisor['id'],$start,$end,$renew,$actor]);$assignmentId=(int)$this->db->insert_id;
     $this->db->execute_query('UPDATE fm_service_profiles SET current_assignment_id=? WHERE id=?',[$assignmentId,$profileId]);
    }
    $this->audit($actor,'import_account',$accountId,['assigned'=>$assigned]);$loaded++;
   }catch(\Throwable $e){
    $this->db->query('ROLLBACK TO SAVEPOINT csv_row');
    if($e instanceof \mysqli_sql_exception&&$e->getCode()===1062)$e=new HttpException(409,'Registro duplicado.');
    if(!$e instanceof HttpException||!in_array($e->status,[422,409],true))throw $e;
    $errors[]=['line'=>is_array($r)?(int)($r['_line']??($index+2)):$index+2,'email'=>is_array($r)?mb_substr((string)($r['correo_secundario']??''),0,254):'','reason'=>$e->getMessage()];
   }finally{$this->db->query('RELEASE SAVEPOINT csv_row');}
  }
  return ['loaded'=>$loaded,'errors'=>$errors,'message'=>$loaded.' filas cargadas; '.count($errors).' rechazadas.'];
 }
 private function payBulk(int $actor,array $input):array {
  $this->admin($actor);$items=$input['items']??null;
  if(!is_array($items)||!$items||count($items)>50)throw new HttpException(422,'Selecciona entre 1 y 50 cuentas principales.');
  $seen=[];foreach($items as $item){if(!is_array($item))throw new HttpException(422,'Selección no válida.');$id=Rules::id($item['main_id']??null);if(isset($seen[$id]))throw new HttpException(422,'Cuenta principal repetida.');$seen[$id]=true;$this->pay($actor,$item);}
  return ['message'=>count($items).' pagos registrados.'];
 }
 public function payments(int $actor,array $filters):array {
  $this->admin($actor);$where='1=1';$args=[];$q=Rules::text($filters['q']??'',100,false);$state=$filters['state']??'';
  if(!in_array($state,['','current','soon','expired'],true))throw new HttpException(422,'Filtro no válido.');
  if($q!==''){$where.=' AND (LOCATE(?,m.email)>0 OR LOCATE(?,m.payment_email)>0)';$args=[$q,$q];}
  if($state==='current')$where.=' AND m.next_payment>DATE_ADD(DATE(UTC_TIMESTAMP() - INTERVAL 5 HOUR),INTERVAL 3 DAY)';
  if($state==='soon')$where.=' AND m.next_payment BETWEEN DATE(UTC_TIMESTAMP() - INTERVAL 5 HOUR) AND DATE_ADD(DATE(UTC_TIMESTAMP() - INTERVAL 5 HOUR),INTERVAL 3 DAY)';
  if($state==='expired')$where.=' AND m.next_payment<DATE(UTC_TIMESTAMP() - INTERVAL 5 HOUR)';
  $size=(int)($filters['size']??20);if(!in_array($size,[10,20,50],true))throw new HttpException(422,'Tamaño no válido.');
  $total=(int)$this->db->execute_query('SELECT COUNT(*) n FROM fm_service_mains m WHERE '.$where,$args)->fetch_assoc()['n'];$pages=max(1,(int)ceil($total/$size));$page=max(1,min($pages,(int)($filters['page']??1)));
  $rows=$this->db->execute_query('SELECT m.id main_id,m.revision main_revision,m.email main_email,m.payment_email,m.next_payment FROM fm_service_mains m WHERE '.$where.' ORDER BY m.next_payment,m.id LIMIT '.$size.' OFFSET '.(($page-1)*$size),$args)->fetch_all(MYSQLI_ASSOC);
  foreach($rows as &$row){$row['payment_days']=Rules::days($row['next_payment']);$row['summary']=$this->db->execute_query("SELECT COUNT(*) total,COALESCE(SUM(c.state='fallen'),0) fallen,COALESCE(SUM(c.state<>'fallen' AND EXISTS(SELECT 1 FROM fm_service_profiles p WHERE p.account_id=c.id AND p.current_assignment_id IS NOT NULL)),0) assigned,COALESCE(SUM(c.state<>'fallen' AND NOT EXISTS(SELECT 1 FROM fm_service_profiles p WHERE p.account_id=c.id AND p.current_assignment_id IS NOT NULL)),0) free FROM fm_service_accounts c WHERE c.main_id=? AND c.state<>'deleted'",[$row['main_id']])->fetch_assoc();}unset($row);
  return ['rows'=>$rows,'page'=>$page,'pages'=>$pages,'total'=>$total];
 }
 private function pay(int $actor,array $input):array {
  $this->admin($actor);$id=Rules::id($input['main_id']??null);
  $main=$this->db->execute_query('SELECT next_payment,revision FROM fm_service_mains WHERE id=? FOR UPDATE',[$id])->fetch_assoc();
  if(!$main)throw new HttpException(404,'Cuenta principal no disponible.');
  if((int)($input['main_revision']??0)!==(int)$main['revision'])throw new HttpException(409,'Los datos de pago cambiaron. Actualiza la tabla.');
  $next=Rules::month($main['next_payment']);
  $this->db->execute_query('UPDATE fm_service_mains SET next_payment=?,revision=revision+1,updated_at=(UTC_TIMESTAMP() - INTERVAL 5 HOUR) WHERE id=?',[$next,$id]);
  $this->db->execute_query('INSERT INTO fm_service_payments(main_id,actor_id,previous_date,next_date,created_at) VALUES(?,?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$id,$actor,$main['next_payment'],$next]);
  $this->audit($actor,'provider_payment',$id,['before'=>$main['next_payment'],'after'=>$next]);
  return ['message'=>'Pago registrado. Próximo pago: '.date('d/m/Y',strtotime($next)).'.'];
 }
 private function service(int $id):array {$row=$this->db->execute_query('SELECT * FROM fm_service_types WHERE id=? AND active=1',[$id])->fetch_assoc();if(!$row)throw new HttpException(422,'Servicio no disponible.');return $row;}
 private function eligible(int $id):void {if((new PermissionRepository($this->db))->isSuperuser($id))throw new HttpException(422,'Admin es una cuenta de TI y no recibe asignaciones.');$this->actor($id);}
 private function assignment(int $actor,array $input):array {
  $who=$this->actor($actor);$id=Rules::id($input['assignment_id']??null);
  $row=$this->db->execute_query('SELECT a.*,p.account_id,p.name profile_name,c.password_cipher,c.revision account_revision,c.state FROM fm_service_assignments a JOIN fm_service_profiles p ON p.current_assignment_id=a.id JOIN fm_service_accounts c ON c.id=p.account_id WHERE a.id=? AND a.closed_at IS NULL FOR UPDATE',[$id])->fetch_assoc();
  if(!$row||(!$who['admin']&&(int)$row['advisor_id']!==$actor))throw new HttpException(404,'Asignación no disponible.');
  if((int)($input['revision']??0)!==(int)$row['revision'])throw new HttpException(409,'La asignación cambió. Actualiza la tabla.');return $row;
 }
 private function save(int $actor,array $input):array {
  $this->admin($actor);$id=(int)($input['id']??0);$service=$this->service(Rules::id($input['service_id']??null));
  $email=Rules::email($input['email']??null);$payment=Rules::email($input['payment_email']??null);$date=Rules::date($input['next_payment']??null);
  $accounts=$input['accounts']??null;
  if(!is_array($accounts)||($id===0&&count($accounts)<1)||count($accounts)>(int)$service['max_accounts'])throw new HttpException(422,'Respeta el máximo de '.$service['max_accounts'].' cuentas secundarias.');
  if($id){$old=$this->db->execute_query('SELECT * FROM fm_service_mains WHERE id=? FOR UPDATE',[$id])->fetch_assoc();if(!$old)throw new HttpException(404,'Cuenta principal no disponible.');if((int)($input['revision']??0)!==(int)$old['revision'])throw new HttpException(409,'La cuenta principal cambió. Abre de nuevo su edición.');if((int)$old['service_id']!==(int)$service['id'])throw new HttpException(422,'No cambies el servicio de una cuenta existente.');
   $this->db->execute_query('UPDATE fm_service_mains SET email=?,payment_email=?,next_payment=?,revision=revision+1,updated_at=(UTC_TIMESTAMP() - INTERVAL 5 HOUR) WHERE id=?',[$email,$payment,$date,$id]);
  }else{$this->db->execute_query('INSERT INTO fm_service_mains(service_id,email,payment_email,next_payment,created_at,updated_at) VALUES(?,?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR),(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$service['id'],$email,$payment,$date]);$id=(int)$this->db->insert_id;}
  $removed=$input['removed_accounts']??[];
  if(!is_array($removed))throw new HttpException(422,'Selección de bajas no válida.');
  foreach($removed as $remove){
   if(!is_array($remove))throw new HttpException(422,'Cuenta no válida.');$removeId=Rules::id($remove['id']??null);
   $oldAccount=$this->db->execute_query("SELECT revision FROM fm_service_accounts WHERE id=? AND main_id=? AND state<>'deleted' FOR UPDATE",[$removeId,$id])->fetch_assoc();
   if(!$oldAccount||(int)$oldAccount['revision']!==(int)($remove['revision']??0))throw new HttpException(409,'La cuenta cambió. Abre de nuevo la edición.');
   if($this->db->execute_query('SELECT id FROM fm_service_profiles WHERE account_id=? AND current_assignment_id IS NOT NULL LIMIT 1 FOR UPDATE',[$removeId])->fetch_assoc())throw new HttpException(409,'No se puede quitar la cuenta: está asignada.');
   $this->db->execute_query("UPDATE fm_service_accounts SET state='deleted',revision=revision+1,updated_at=(UTC_TIMESTAMP() - INTERVAL 5 HOUR) WHERE id=?",[$removeId]);$this->audit($actor,'remove_account',$removeId);
  }
  $seen=[];
  foreach($accounts as $account){
   if(!is_array($account))throw new HttpException(422,'Cuenta secundaria no válida.');
   $assignedAccount=false;$accountId=(int)($account['id']??0);$mail=Rules::email($account['email']??null);$password=Rules::password($account['password']??null);$profiles=$account['profiles']??[['name'=>'Perfil 1']];
   if(!is_array($profiles)||!$profiles||count($profiles)>(int)$service['max_profiles'])throw new HttpException(422,'Respeta el máximo de '.$service['max_profiles'].' perfiles por cuenta.');
   if($accountId){if(isset($seen[$accountId]))throw new HttpException(422,'Cuenta secundaria repetida.');$oldAccount=$this->db->execute_query('SELECT * FROM fm_service_accounts WHERE id=? AND main_id=? AND state<>"deleted" FOR UPDATE',[$accountId,$id])->fetch_assoc();if(!$oldAccount)throw new HttpException(422,'La cuenta secundaria no pertenece a esta principal.');if((int)($account['revision']??0)!==(int)$oldAccount['revision'])throw new HttpException(409,'Cambió una contraseña o un perfil. Actualiza la edición.');$assignedAccount=(bool)$this->db->execute_query('SELECT id FROM fm_service_profiles WHERE account_id=? AND current_assignment_id IS NOT NULL LIMIT 1',[$accountId])->fetch_assoc();
    if($assignedAccount&&($mail!==$oldAccount['email']||!hash_equals($this->vault->decrypt($oldAccount['password_cipher']),$password)))throw new HttpException(409,'Los campos de una cuenta asignada no se pueden editar desde este formulario.');
    $this->db->execute_query('UPDATE fm_service_accounts SET email=?,password_cipher=?,revision=revision+1,updated_at=(UTC_TIMESTAMP() - INTERVAL 5 HOUR) WHERE id=?',[$mail,$this->vault->encrypt($password),$accountId]);
   }else{$this->db->execute_query('INSERT INTO fm_service_accounts(main_id,email,password_cipher,created_at,updated_at) VALUES(?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR),(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$id,$mail,$this->vault->encrypt($password)]);$accountId=(int)$this->db->insert_id;}
   $seen[$accountId]=true;$profileSeen=[];
   foreach($profiles as $profile){if(!is_array($profile))throw new HttpException(422,'Perfil no válido.');$profileId=(int)($profile['id']??0);$name=Rules::text($profile['name']??'',80,false)?:'Perfil 1';
    if($profileId){if(isset($profileSeen[$profileId]))throw new HttpException(422,'Perfil repetido.');$exists=$this->db->execute_query('SELECT id,name FROM fm_service_profiles WHERE id=? AND account_id=?',[$profileId,$accountId])->fetch_assoc();if(!$exists)throw new HttpException(422,'Perfil ajeno a la cuenta.');if($assignedAccount&&$exists['name']!==$name)throw new HttpException(409,'El perfil de una cuenta asignada no se puede editar desde este formulario.');$this->db->execute_query('UPDATE fm_service_profiles SET name=? WHERE id=?',[$name,$profileId]);}
    else{$this->db->execute_query('INSERT INTO fm_service_profiles(account_id,name) VALUES(?,?)',[$accountId,$name]);$profileId=(int)$this->db->insert_id;}$profileSeen[$profileId]=true;
   }
   if((int)$this->db->execute_query('SELECT COUNT(*) n FROM fm_service_profiles WHERE account_id=?',[$accountId])->fetch_assoc()['n']!==count($profileSeen))throw new HttpException(422,'No se pueden omitir perfiles existentes.');
  }
  if((int)$this->db->execute_query('SELECT COUNT(*) n FROM fm_service_accounts WHERE main_id=? AND state<>"deleted"',[$id])->fetch_assoc()['n']!==count($seen))throw new HttpException(422,'No se pueden omitir cuentas existentes.');
  $this->audit($actor,'save_main',$id,['next_payment'=>$date]);return ['id'=>$id,'message'=>'Cuenta principal guardada.'];
 }
 public function main(int $actor,int $id):array {
  $this->admin($actor);$row=$this->db->execute_query('SELECT * FROM fm_service_mains WHERE id=?',[$id])->fetch_assoc();if(!$row)throw new HttpException(404,'Cuenta principal no disponible.');
  $row['accounts']=$this->db->execute_query('SELECT id,email,password_cipher,revision,EXISTS(SELECT 1 FROM fm_service_profiles p WHERE p.account_id=fm_service_accounts.id AND p.current_assignment_id IS NOT NULL) assigned FROM fm_service_accounts WHERE main_id=? AND state<>"deleted" ORDER BY id',[$id])->fetch_all(MYSQLI_ASSOC);
  foreach($row['accounts'] as &$account){$account['password']=$this->vault->decrypt($account['password_cipher']);unset($account['password_cipher']);$account['profiles']=$this->db->execute_query('SELECT id,name FROM fm_service_profiles WHERE account_id=? ORDER BY id',[$account['id']])->fetch_all(MYSQLI_ASSOC);}unset($account);return $row;
 }
 public function client(int $actor,string $phone):?array {$this->actor($actor);$phone=Rules::phone($phone);return $this->db->execute_query('SELECT phone,name FROM fm_clients WHERE phone=?',[$phone])->fetch_assoc();}
 private function obtain(int $actor,array $input):array {
  $who=$this->actor($actor);$target=$who['admin']?Rules::id($input['advisor_id']??null):$actor;$this->eligible($target);
  $service=$this->service(Rules::id($input['service_id']??null));$phone=Rules::phone($input['phone']??null);$name=Rules::text($input['client_name']??'',150);$start=Rules::date($input['start_date']??null);
  $client=$this->db->execute_query('SELECT phone,name FROM fm_clients WHERE phone=? FOR UPDATE',[$phone])->fetch_assoc();
  if(!$client){$this->db->execute_query('INSERT INTO fm_clients(phone,name,created_by,created_at) VALUES(?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$phone,$name,$actor]);$this->audit($actor,'create_client',0);}
  elseif($client['name']!==$name)throw new HttpException(409,'El celular ya pertenece a un cliente. Usa su nombre registrado.');
  $sql="SELECT p.id FROM fm_service_profiles p JOIN fm_service_accounts c ON c.id=p.account_id JOIN fm_service_mains m ON m.id=c.main_id WHERE m.service_id=? AND c.state='enabled' AND p.current_assignment_id IS NULL";$args=[$service['id']];
  if(!empty($input['profile_id'])){if(!$who['admin'])throw new HttpException(403,'No puedes seleccionar el inventario libre.');$sql.=' AND p.id=?';$args[]=Rules::id($input['profile_id']);}
  $profile=$this->db->execute_query($sql.' ORDER BY RAND() LIMIT 1 FOR UPDATE',$args)->fetch_assoc();
  if(!$profile)return ['available'=>false,'message'=>'No hay cuentas disponibles. El cliente quedó registrado.'];
  $this->db->execute_query('INSERT INTO fm_service_assignments(profile_id,client_phone,advisor_id,start_date,end_date,created_by,created_at) VALUES(?,?,?,?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$profile['id'],$phone,$target,$start,Rules::month($start),$actor]);$id=(int)$this->db->insert_id;
  $this->db->execute_query('UPDATE fm_service_profiles SET current_assignment_id=? WHERE id=? AND current_assignment_id IS NULL',[$id,$profile['id']]);
  if($who['admin']&&array_key_exists('password',$input)){$row=$this->assignment($actor,['assignment_id'=>$id,'revision'=>1]);$this->changeCredentials($actor,$input,$row,false);}
  $this->audit($actor,'obtain',$id,['advisor_id'=>$target,'seller_ids'=>[$target]]);return ['available'=>true,'assignment_id'=>$id,'message'=>'Cuenta asignada.'];
 }
 public function assignedDetails(int $actor,int $id):array {
  $who=$this->actor($actor);$row=$this->db->execute_query('SELECT a.id,a.revision,a.advisor_id,p.name profile,c.email,c.password_cipher,c.revision account_revision FROM fm_service_assignments a JOIN fm_service_profiles p ON p.current_assignment_id=a.id JOIN fm_service_accounts c ON c.id=p.account_id WHERE a.id=? AND a.closed_at IS NULL',[$id])->fetch_assoc();
  if(!$row||(!$who['admin']&&(int)$row['advisor_id']!==$actor))throw new HttpException(404,'Asignación no disponible.');$row['password']=$this->vault->decrypt($row['password_cipher']);unset($row['password_cipher']);return $row;
 }
 private function renew(int $actor,array $input):array {
  $row=$this->assignment($actor,$input);$end=Rules::month($row['end_date']);
  $this->db->execute_query('INSERT INTO fm_service_renewals(assignment_id,previous_end,new_end,actor_id,created_at) VALUES(?,?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$row['id'],$row['end_date'],$end,$actor]);
  $this->db->execute_query('UPDATE fm_service_assignments SET end_date=?,last_renewed_at=(UTC_TIMESTAMP() - INTERVAL 5 HOUR),revision=revision+1 WHERE id=?',[$end,$row['id']]);$this->audit($actor,'renew',(int)$row['id'],['before'=>$row['end_date'],'after'=>$end,'seller_ids'=>[(int)$row['advisor_id']]]);return ['message'=>'Servicio renovado hasta '.$end.'.'];
 }
 private function changeCredentials(int $actor,array $input,array $row,bool $release):void {
  if((int)($input['account_revision']??0)!==(int)$row['account_revision'])throw new HttpException(409,'Cambió la contraseña o el perfil. Actualiza la tabla.');
  $password=Rules::password($input['password']??null);$current=$this->vault->decrypt($row['password_cipher']);
  if($release&&hash_equals($current,$password))throw new HttpException(422,'Registra una contraseña diferente antes de liberar.');
  $others=(int)$this->db->execute_query('SELECT COUNT(*) n FROM fm_service_profiles WHERE account_id=? AND current_assignment_id IS NOT NULL AND current_assignment_id<>?',[$row['account_id'],$row['id']])->fetch_assoc()['n'];
  if($others&&!hash_equals($current,$password))throw new HttpException(409,'La contraseña está compartida con otros perfiles. Se requiere revisión administrativa antes del cambio.');
  $name=Rules::text($input['profile_name']??'',80,false)?:$row['profile_name'];
  $this->db->execute_query('UPDATE fm_service_accounts SET password_cipher=?,revision=revision+1,updated_at=(UTC_TIMESTAMP() - INTERVAL 5 HOUR) WHERE id=?',[$this->vault->encrypt($password),$row['account_id']]);
  $this->db->execute_query('UPDATE fm_service_profiles SET name=? WHERE id=?',[$name,$row['profile_id']]);$this->audit($actor,'credentials',(int)$row['account_id']);
 }
 private function credentials(int $actor,array $input):array {$row=$this->assignment($actor,$input);$this->changeCredentials($actor,$input,$row,false);return ['message'=>'Contraseña y perfil registrados.'];}
 private function release(int $actor,array $input):array {
  $row=$this->assignment($actor,$input);$this->changeCredentials($actor,$input,$row,true);
  $this->db->execute_query("UPDATE fm_service_assignments SET closed_at=(UTC_TIMESTAMP() - INTERVAL 5 HOUR),close_reason='release',revision=revision+1 WHERE id=?",[$row['id']]);$this->db->execute_query('UPDATE fm_service_profiles SET current_assignment_id=NULL WHERE id=?',[$row['profile_id']]);$this->audit($actor,'release',(int)$row['id'],['seller_ids'=>[(int)$row['advisor_id']]]);return ['message'=>'Cuenta liberada.'];
 }
 private function fall(int $actor,array $input):array {
  if(!empty($input['assignment_id'])){$row=$this->assignment($actor,$input);$accountId=(int)$row['account_id'];$revision=(int)($input['account_revision']??0);}
  else{$this->admin($actor);$accountId=Rules::id($input['account_id']??null);$revision=(int)($input['account_revision']??0);}
  $account=$this->db->execute_query('SELECT revision,state FROM fm_service_accounts WHERE id=? FOR UPDATE',[$accountId])->fetch_assoc();if(!$account)throw new HttpException(404,'Cuenta no disponible.');if((int)$account['revision']!==$revision||$account['state']!=='enabled')throw new HttpException(409,'El estado de la cuenta cambió.');
  $reason=Rules::text($input['reason']??'',500,false);
  $sellers=array_map('intval',array_column($this->db->execute_query('SELECT DISTINCT a.advisor_id FROM fm_service_profiles p JOIN fm_service_assignments a ON a.id=p.current_assignment_id WHERE p.account_id=?',[$accountId])->fetch_all(MYSQLI_ASSOC),'advisor_id'));
  $this->db->execute_query("UPDATE fm_service_accounts SET state='fallen',fallen_reason=?,revision=revision+1,updated_at=(UTC_TIMESTAMP() - INTERVAL 5 HOUR) WHERE id=?",[$reason,$accountId]);
  $this->db->execute_query("UPDATE fm_service_assignments a JOIN fm_service_profiles p ON p.current_assignment_id=a.id SET a.closed_at=(UTC_TIMESTAMP() - INTERVAL 5 HOUR),a.close_reason='fallen',a.revision=a.revision+1 WHERE p.account_id=?",[$accountId]);
  $this->db->execute_query('UPDATE fm_service_profiles SET current_assignment_id=NULL WHERE account_id=?',[$accountId]);$this->audit($actor,'fall',$accountId,['seller_ids'=>$sellers]);return ['message'=>'Cuenta marcada como caída. Queda fuera de disponibles.'];
 }
 private function rehabilitate(int $actor,array $input):array {
  $this->admin($actor);$id=Rules::id($input['account_id']??null);$row=$this->db->execute_query('SELECT revision,state FROM fm_service_accounts WHERE id=? FOR UPDATE',[$id])->fetch_assoc();if(!$row)throw new HttpException(404,'Cuenta no disponible.');if((int)$row['revision']!==(int)($input['account_revision']??0)||$row['state']!=='fallen')throw new HttpException(409,'El estado de la cuenta cambió.');
  if(array_key_exists('password',$input)){
   $profileId=Rules::id($input['profile_id']??null);
   $details=$this->db->execute_query('SELECT c.id account_id,c.revision account_revision,c.password_cipher,p.id profile_id,p.name profile_name,0 id FROM fm_service_accounts c JOIN fm_service_profiles p ON p.account_id=c.id WHERE c.id=? AND p.id=? FOR UPDATE',[$id,$profileId])->fetch_assoc();
   if(!$details)throw new HttpException(422,'Perfil no válido para esta cuenta.');$this->changeCredentials($actor,$input,$details,false);
  }
  $this->db->execute_query("UPDATE fm_service_accounts SET state='enabled',fallen_reason=NULL,revision=revision+1,updated_at=(UTC_TIMESTAMP() - INTERVAL 5 HOUR) WHERE id=?",[$id]);$this->audit($actor,'rehabilitate',$id);return ['message'=>'Cuenta habilitada.'];
 }
 private function transfer(int $actor,array $input):array {
  $this->admin($actor);$row=$this->assignment($actor,$input);$target=Rules::id($input['advisor_id']??null);$this->eligible($target);if($target===(int)$row['advisor_id'])throw new HttpException(422,'Selecciona otro asesor.');
  if(array_key_exists('password',$input))$this->changeCredentials($actor,$input,$row,false);
  $this->db->execute_query('UPDATE fm_service_assignments SET advisor_id=?,revision=revision+1 WHERE id=?',[$target,$row['id']]);$this->audit($actor,'transfer',(int)$row['id'],['from'=>(int)$row['advisor_id'],'to'=>$target]);return ['message'=>'Cuenta reasignada.'];
 }
 public function dashboard(int $actor):array {
  $who=$this->actor($actor);$args=[];$scope="c.state<>'deleted'";
  if(!$who['admin']){
   $scope.=" AND (EXISTS(SELECT 1 FROM fm_service_profiles p JOIN fm_service_assignments a ON a.id=p.current_assignment_id WHERE p.account_id=c.id AND a.advisor_id=?) OR (c.state='fallen' AND EXISTS(SELECT 1 FROM fm_service_profiles p JOIN fm_service_assignments a ON a.profile_id=p.id WHERE p.account_id=c.id AND a.advisor_id=? AND a.close_reason='fallen' AND a.id=(SELECT MAX(ax.id) FROM fm_service_assignments ax WHERE ax.profile_id=p.id))))";$args=[$actor,$actor];
  }
  $counts=['assigned'=>0,'free'=>0,'fallen'=>0];
  $rows=$this->db->execute_query("SELECT c.state,EXISTS(SELECT 1 FROM fm_service_profiles p WHERE p.account_id=c.id AND p.current_assignment_id IS NOT NULL) assigned FROM fm_service_accounts c WHERE ".$scope,$args)->fetch_all(MYSQLI_ASSOC);
  foreach($rows as $r)$counts[$r['state']==='fallen'?'fallen':($r['assigned']?'assigned':'free')]++;
  $payments=['current'=>0,'soon'=>0,'expired'=>0];$renewals=$payments;
  if($who['admin'])foreach($this->db->query('SELECT next_payment FROM fm_service_mains')->fetch_all(MYSQLI_ASSOC) as $r){$days=Rules::days($r['next_payment']);$payments[$days<0?'expired':($days<=3?'soon':'current')]++;}
  $sql='SELECT a.end_date FROM fm_service_assignments a JOIN fm_service_profiles p ON p.current_assignment_id=a.id WHERE a.closed_at IS NULL';$params=[];
  if(!$who['admin']){$sql.=' AND a.advisor_id=?';$params=[$actor];}
  foreach($this->db->execute_query($sql,$params)->fetch_all(MYSQLI_ASSOC) as $r){$days=Rules::days($r['end_date']);$renewals[$days<0?'expired':($days<=3?'soon':'current')]++;}
  return ['admin'=>$who['admin'],'payments'=>$who['admin']?$payments:null,'renewals'=>$renewals,'accounts'=>$counts];
 }
 public function metadata(int $actor):array {
  $who=$this->actor($actor);$types=$this->db->query('SELECT id,name,max_accounts,max_profiles FROM fm_service_types WHERE active=1 ORDER BY name')->fetch_all(MYSQLI_ASSOC);
  foreach($types as &$type)$type['available']=(int)$this->db->execute_query("SELECT COUNT(*) n FROM fm_service_profiles p JOIN fm_service_accounts c ON c.id=p.account_id JOIN fm_service_mains m ON m.id=c.main_id WHERE m.service_id=? AND c.state='enabled' AND p.current_assignment_id IS NULL",[$type['id']])->fetch_assoc()['n'];unset($type);
  $users=[];$orphans=0;
  if($who['admin']){foreach(UserNames::all($this->db) as $user){$identity=(new UserRepository($this->db))->identity((int)$user['id']);$eligible=$identity&&(int)$identity['estado']===1&&!(new PermissionRepository($this->db))->isSuperuser((int)$user['id'])&&!empty((new PermissionRepository($this->db))->effective((int)$user['id'])['services.spotify']);$assigned=(int)$this->db->execute_query('SELECT COUNT(*) n FROM fm_service_assignments WHERE advisor_id=? AND closed_at IS NULL',[$user['id']])->fetch_assoc()['n'];if(!$eligible)$orphans+=$assigned;if($eligible||$assigned)$users[]=['id'=>(int)$user['id'],'name'=>$user['display_name'],'eligible'=>(bool)$eligible];}}
  return ['services'=>$types,'users'=>$users,'orphaned'=>$orphans,'fallen'=>$who['admin']?(int)$this->db->query("SELECT COUNT(*) n FROM fm_service_accounts WHERE state='fallen'")->fetch_assoc()['n']:0,'admin'=>$who['admin']];
 }
 public function listing(int $actor,array $filters):array {
  $who=$this->actor($actor);$args=[];$where="c.state<>'deleted'";
  if(!$who['admin']){$where.=' AND a.advisor_id=? AND a.closed_at IS NULL';$args[]=$actor;}
  $state=$filters['state']??'';
  if(!in_array($state,['','available','assigned','fallen'],true))throw new HttpException(422,'Estado no válido.');
  if($state==='available')$where.=" AND c.state='enabled' AND p.current_assignment_id IS NULL AND s.active=1";
  if($state==='assigned')$where.=' AND p.current_assignment_id IS NOT NULL';
  if($state==='fallen')$where.=" AND c.state='fallen' AND p.id=(SELECT MIN(px.id) FROM fm_service_profiles px WHERE px.account_id=c.id)";
  if(!empty($filters['service_id'])){$where.=' AND s.id=?';$args[]=Rules::id($filters['service_id']);}
  if($who['admin']&&!empty($filters['advisor_id'])){$where.=' AND a.advisor_id=?';$args[]=Rules::id($filters['advisor_id']);}
  $search=Rules::text($filters['q']??'',100,false);
  if($search!==''){$where.=" AND (LOCATE(?,c.email)>0 OR LOCATE(?,cl.name)>0 OR LOCATE(?,cl.phone)>0 OR LOCATE(?,p.name)>0";$args=array_merge($args,[$search,$search,$search,$search]);if($who['admin']){$where.=' OR LOCATE(?,m.email)>0';$args[]=$search;}$where.=')';}
  $expiry=$filters['expiry']??'';if(!in_array($expiry,['','expired','soon','current'],true))throw new HttpException(422,'Filtro de vencimiento no válido.');
  if($expiry==='current')$where.=' AND a.end_date>DATE_ADD(DATE(UTC_TIMESTAMP() - INTERVAL 5 HOUR),INTERVAL 3 DAY)';
  if($expiry==='expired')$where.=' AND a.end_date<DATE(UTC_TIMESTAMP() - INTERVAL 5 HOUR)';if($expiry==='soon')$where.=' AND a.end_date BETWEEN DATE(UTC_TIMESTAMP() - INTERVAL 5 HOUR) AND DATE_ADD(DATE(UTC_TIMESTAMP() - INTERVAL 5 HOUR),INTERVAL 3 DAY)';
  $from=' FROM fm_service_profiles p JOIN fm_service_accounts c ON c.id=p.account_id JOIN fm_service_mains m ON m.id=c.main_id JOIN fm_service_types s ON s.id=m.service_id LEFT JOIN fm_service_assignments a ON a.id=p.current_assignment_id LEFT JOIN fm_clients cl ON cl.phone=a.client_phone';
  $total=(int)$this->db->execute_query('SELECT COUNT(*) n'.$from.' WHERE '.$where,$args)->fetch_assoc()['n'];
  $size=(int)($filters['size']??20);if(!in_array($size,[10,20,50],true))throw new HttpException(422,'Tamaño de página no válido.');$pages=max(1,(int)ceil($total/$size));$page=max(1,min($pages,(int)($filters['page']??1)));$offset=($page-1)*$size;
  $columns='p.id profile_id,p.name profile,c.id account_id,c.email,c.password_cipher,c.state,c.revision account_revision,s.id service_id,s.name service,a.id assignment_id,a.revision,a.advisor_id,a.start_date,a.end_date,a.last_renewed_at,cl.name client_name,cl.phone';
  if($who['admin'])$columns.=',m.id main_id,m.revision main_revision,m.email main_email,m.payment_email,m.next_payment,c.fallen_reason,(SELECT sa.actor_id FROM fm_service_audit sa WHERE sa.entity_id=c.id AND sa.action="fall" ORDER BY sa.id DESC LIMIT 1) fallen_reporter_id';
  $rows=$this->db->execute_query('SELECT '.$columns.$from.' WHERE '.$where.' ORDER BY m.id DESC,c.id DESC,p.id LIMIT '.$size.' OFFSET '.$offset,$args)->fetch_all(MYSQLI_ASSOC);$names=UserNames::all($this->db);
  foreach($rows as &$row){$row['password']=$this->vault->decrypt($row['password_cipher']);unset($row['password_cipher']);$row['advisor_name']=$names[$row['advisor_id']]['display_name']??null;if($who['admin'])$row['fallen_reporter_name']=$row['fallen_reporter_id']===null?null:($names[$row['fallen_reporter_id']]['display_name']??'Usuario no disponible');$row['days']=Rules::days($row['end_date']);if($who['admin'])$row['payment_days']=Rules::days($row['next_payment']);}unset($row);
  return ['rows'=>$rows,'total'=>$total,'page'=>$page,'pages'=>$pages];
 }
}
