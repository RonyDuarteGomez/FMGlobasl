<?php
namespace FMGlobal\Repositories;
use FMGlobal\Http\HttpException;
use FMGlobal\Services\Spotify\Rules;
use FMGlobal\Services\Schedules\WeeklySchedule;
use FMGlobal\Services\Links\LinkGenerator;
final class ExternalLinkRepository {
 public function __construct(private \mysqli $db,private ?LinkGenerator $generator=null){}
 public function authorize(int $actor,string $permission):array {$u=(new UserRepository($this->db))->identity($actor);if(!$u||(int)$u['estado']!==1||empty((new PermissionRepository($this->db))->effective($actor)[$permission]))throw new HttpException(403,'No tienes permiso para esta opción.');return $u;}
 private function audit(int $actor,string $action,string $type,int $id,array $data):void {$this->db->execute_query('INSERT INTO fm_external_audit(actor_id,action,target_type,target_id,details_json,created_at) VALUES(?,?,?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$actor,$action,$type,$id,json_encode($data,JSON_THROW_ON_ERROR)]);}
 private function row(string $type,int $id):array {return $this->db->execute_query('SELECT * FROM fm_external_rules WHERE target_type=? AND target_id=?',[$type,$id])->fetch_assoc()??['revision'=>0,'settings_json'=>'{}'];}
 public function effective(int $actor):array {$user=(new UserRepository($this->db))->identity($actor);if(!$user)throw new HttpException(404,'Usuario no disponible.');$result=['schedule'=>false,'limit'=>0,'browser'=>false];foreach([['role',(int)$user['rol_id']],['user',$actor]] as [$type,$id])foreach(json_decode($this->row($type,$id)['settings_json'],true) as $k=>$v)if($v!==null)$result[$k]=$v;return $result;}
 public function configuration(int $actor,string $type,int $id):array {
  $this->authorize($actor,'external.restrictions');$this->target($type,$id);$row=$this->row($type,$id);$users=array_values(UserNames::selectable($this->db));
  $profiles=array_column($this->db->query("SELECT u.id,u.estado,COALESCE(r.rol_nombre,'Sin perfil') profile_name FROM usuarios u LEFT JOIN personal p ON p.usuario_id=u.id LEFT JOIN rol r ON r.rol_id=p.rol_id")->fetch_all(MYSQLI_ASSOC),null,'id');
  $permissions=new PermissionRepository($this->db);
  foreach($users as &$user){$user['profile_name']=$profiles[$user['id']]['profile_name']??'Sin perfil';$user['active']=(int)($profiles[$user['id']]['estado']??0)===1;$user['has_access']=!empty($permissions->effective((int)$user['id'])['services.external_links']);}unset($user);
  $users=array_values(array_filter($users,static fn(array $user):bool=>$user['active']&&$user['has_access']));

  $pendingIds=array_column($this->db->query("SELECT DISTINCT user_id FROM fm_external_browsers WHERE state='pending'")->fetch_all(MYSQLI_ASSOC),'user_id');
  $pendingUsers=array_values(array_filter($users,fn(array $user):bool=>in_array($user['id'],$pendingIds)&&$this->effective((int)$user['id'])['browser']));
  $browsers=$type==='user'?$this->db->execute_query("SELECT id,label,state,created_at FROM fm_external_browsers WHERE user_id=? AND state<>'revoked' ORDER BY id DESC",[$id])->fetch_all(MYSQLI_ASSOC):[];
  $inherited=$type==='user'?array_replace(['schedule'=>false,'limit'=>0,'browser'=>false],json_decode($this->row('role',(int)(new UserRepository($this->db))->identity($id)['rol_id'])['settings_json'],true)):null;
  return ['pending_browser_users'=>$pendingUsers,'inherited'=>$inherited,'settings'=>json_decode($row['settings_json'],true),'revision'=>(int)$row['revision'],'users'=>$users,'roles'=>$this->db->query('SELECT rol_id,rol_nombre FROM rol')->fetch_all(MYSQLI_ASSOC),'browsers'=>$browsers,'effective'=>$type==='user'?$this->effective($id):null];
 }
 private function target(string $type,int $id):void {if(!in_array($type,['role','user'],true)||!($type==='role'?(new UserRepository($this->db))->roleExists($id):(new UserRepository($this->db))->identity($id)))throw new HttpException(422,'Selecciona un perfil o usuario válido.');}
 public function save(int $actor,array $input):array {
  $this->authorize($actor,'external.restrictions');$type=$input['type']??'';$id=Rules::id($input['id']??null);$this->target($type,$id);$settings=$input['settings']??null;if(!is_array($settings))throw new HttpException(422,'Configuración no válida.');
  $clean=[];foreach(['schedule','limit','browser'] as $key){$value=$settings[$key]??null;if($value===null){$clean[$key]=null;continue;}if($key==='schedule')$value=$value===false?false:WeeklySchedule::validate($value);elseif($key==='limit'){$value=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>100000]]);if($value===false)throw new HttpException(422,'Cupo no válido (0 significa sin límite).');}elseif(!is_bool($value))throw new HttpException(422,'Restricción de navegador no válida.');$clean[$key]=$value;}
  $this->db->begin_transaction();try{(new PermissionRepository($this->db))->lock();$this->authorize($actor,'external.restrictions');$old=$this->row($type,$id);if((int)$old['revision']!==(int)($input['revision']??-1))throw new HttpException(409,'La configuración cambió. Vuelve a cargarla.');
   $this->db->execute_query('INSERT INTO fm_external_rules(target_type,target_id,settings_json) VALUES(?,?,?) ON DUPLICATE KEY UPDATE settings_json=VALUES(settings_json),revision=revision+1',[$type,$id,json_encode($clean)]);$this->audit($actor,'settings',$type,$id,['before'=>json_decode($old['settings_json'],true),'after'=>$clean]);$this->db->commit();return ['message'=>'Restricciones guardadas.'];
  }catch(\Throwable $e){$this->db->rollback();throw $e;}
 }
 public function browserAction(int $actor,array $input):array {
  $this->authorize($actor,'external.restrictions');$id=Rules::id($input['user_id']??null);$this->target('user',$id);$action=$input['operation']??'';if(!in_array($action,['approve','reset'],true))throw new HttpException(422,'Acción no válida.');
  $this->db->begin_transaction();try{(new PermissionRepository($this->db))->lock();$this->authorize($actor,'external.restrictions');
   if($action==='approve'){$browser=$this->db->execute_query("SELECT id FROM fm_external_browsers WHERE id=? AND user_id=? AND state='pending'",[Rules::id($input['browser_id']??null),$id])->fetch_assoc();if(!$browser)throw new HttpException(409,'Solicitud no disponible.');}
   if($action==='reset')$this->db->execute_query('DELETE FROM fm_external_browsers WHERE user_id=?',[$id]);
   else $this->db->execute_query("UPDATE fm_external_browsers SET state='revoked',session_hash=NULL WHERE user_id=?",[$id]);
   if($action==='approve')$this->db->execute_query("UPDATE fm_external_browsers SET state='approved',approved_by=? WHERE id=?",[$actor,$browser['id']]);
   $this->audit($actor,$action,'user',$id,['browser_id'=>$browser['id']??null]);$this->db->commit();return ['message'=>$action==='approve'?'Navegador autorizado.':'Vinculaciones eliminadas. El usuario puede registrar su navegador como en el primer acceso, sin aprobación.'];
  }catch(\Throwable $e){$this->db->rollback();throw $e;}
 }
 public function requestBrowser(int $actor,string $token,string $label):array {
  $this->authorize($actor,'services.external_links');if(!$this->effective($actor)['browser'])throw new HttpException(422,'No se exige vinculación para tu usuario.');
  $this->db->begin_transaction();try{(new PermissionRepository($this->db))->lock();$this->authorize($actor,'services.external_links');
   $hash=hash('sha256',$token);$existing=$this->db->execute_query('SELECT id,state FROM fm_external_browsers WHERE user_id=? AND token_hash=?',[$actor,$hash])->fetch_assoc();
   if($existing&&$existing['state']==='approved')throw new HttpException(409,'Este navegador ya está autorizado.');
   if((!$existing||$existing['state']!=='pending')&&(int)$this->db->execute_query("SELECT COUNT(*) n FROM fm_external_browsers WHERE user_id=? AND state='pending'",[$actor])->fetch_assoc()['n']>=5)throw new HttpException(429,'Ya tienes solicitudes pendientes. Contacta al administrador.');
   $this->db->execute_query("INSERT INTO fm_external_browsers(user_id,token_hash,label,created_at) VALUES(?,?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR)) ON DUPLICATE KEY UPDATE state='pending',label=VALUES(label),session_hash=NULL",[$actor,$hash,Rules::text($label,200)]);
   $this->db->commit();return ['message'=>'Solicitud de cambio enviada. El administrador debe autorizar este navegador.'];
  }catch(\Throwable $e){$this->db->rollback();throw $e;}
 }
 private function claimBrowser(int $actor,string $token,string $session,string $label,bool $register=false):void {
  $this->db->begin_transaction();try{(new PermissionRepository($this->db))->lock();$this->authorize($actor,'services.external_links');
   if($this->effective($actor)['browser']){
    $hash=hash('sha256',$token);
    $bound=$this->db->execute_query("SELECT id FROM fm_external_browsers WHERE user_id=? AND state IN ('approved','revoked') LIMIT 1",[$actor])->fetch_assoc();
    if($register&&$bound)throw new HttpException(409,'Ya existe una vinculación previa. Solicita autorización para cambiar de navegador.');
    if($register&&!$bound){
     $this->db->execute_query("INSERT INTO fm_external_browsers(user_id,token_hash,state,label,session_hash,created_at) VALUES(?,?,'approved',?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR)) ON DUPLICATE KEY UPDATE state='approved',label=VALUES(label),session_hash=VALUES(session_hash)",[$actor,$hash,Rules::text($label,200),hash('sha256',$session)]);
     $this->audit($actor,'browser_initial','user',$actor,[]);
    }
    $this->db->execute_query("UPDATE fm_external_browsers SET session_hash=? WHERE user_id=? AND token_hash=? AND state='approved'",[hash('sha256',$session),$actor,$hash]);
   }$this->db->commit();
  }catch(\Throwable $e){$this->db->rollback();throw $e;}
 }
 public function registerBrowser(int $actor,string $token,string $session,string $label):array {
  $this->authorize($actor,'services.external_links');
  if(!$this->effective($actor)['browser'])throw new HttpException(422,'Tu usuario no tiene restricción por navegador.');
  $this->claimBrowser($actor,$token,$session,$label,true);
  return ['message'=>'Navegador registrado. Solo podrás utilizar el servicio desde este navegador.'];
 }
 public function status(int $actor,string $token,string $session,bool $claim=false,string $label='Navegador'):array {
  if($claim)$this->claimBrowser($actor,$token,$session,$label);
  $this->authorize($actor,'services.external_links');$rules=$this->effective($actor);$reasons=[];$next=null;$now=new \DateTimeImmutable('now',new \DateTimeZone('America/Lima'));
  if($rules['schedule']!==false&&!WeeklySchedule::active($rules['schedule'],$now)){$reasons[]='Fuera del horario permitido (Lima).';for($offset=0;$offset<=7;$offset++){$day=$now->modify('+'.$offset.' days');$schedule=$rules['schedule'][(int)$day->format('N')];$starts=$schedule['mode']==='all'?['00:00']:($schedule['mode']==='hours'?array_column($schedule['slots'],'start'):[]);foreach($starts as $time){$candidate=$day->format('Y-m-d').' '.$time;if($candidate>$now->format('Y-m-d H:i')){$next=$candidate;break 2;}}}if($next)$reasons[]='Próximo horario: '.$next;}
  $count=(int)$this->db->execute_query('SELECT COUNT(*) n FROM fm_external_attempts WHERE user_id=? AND created_at>=DATE(UTC_TIMESTAMP() - INTERVAL 5 HOUR) AND created_at<DATE(UTC_TIMESTAMP() - INTERVAL 5 HOUR)+INTERVAL 1 DAY',[$actor])->fetch_assoc()['n'];
  if($rules['limit']>0&&$count>=$rules['limit'])$reasons[]='Cupo diario agotado. Se renueva a las 00:00 de Lima.';
  $browser=null;$registrationAvailable=false;$linked=false;
  if($rules['browser']){
   $history=$this->db->execute_query("SELECT COALESCE(SUM(state='approved'),0) approved,COALESCE(SUM(state IN ('approved','revoked')),0) history FROM fm_external_browsers WHERE user_id=?",[$actor])->fetch_assoc();
   $linked=(int)$history['approved']>0;$registrationAvailable=(int)$history['history']===0;
   $browser=$this->db->execute_query('SELECT id,state,session_hash FROM fm_external_browsers WHERE user_id=? AND token_hash=?',[$actor,hash('sha256',$token)])->fetch_assoc();
   if(!$browser||$browser['state']!=='approved'){
    if($registrationAvailable)$reasons[]='Aún no tienes un navegador registrado. Registra este navegador para utilizar el servicio.';
    elseif($browser&&$browser['state']==='pending')$reasons[]='Tu solicitud está pendiente de autorización del administrador.';
    elseif($linked)$reasons[]='Ya tienes otro navegador vinculado. Solicita el cambio para utilizar este.';
    else $reasons[]='La vinculación anterior fue revocada. Solicita autorización para utilizar este navegador.';
   }elseif(!hash_equals($browser['session_hash']??'',hash('sha256',$session)))$reasons[]='La sesión operativa cambió. Abre nuevamente el módulo.';
  }
  $remaining=$rules['limit']>0?max(0,$rules['limit']-$count):null;
  $seconds=$rules['schedule']===false?null:WeeklySchedule::remainingSeconds($rules['schedule'],$now);
  $browserOk=!$rules['browser']||($browser&&$browser['state']==='approved'&&hash_equals($browser['session_hash']??'',hash('sha256',$session)));
  $indicators=[
   'attempts'=>['level'=>$remaining===0?'danger':($remaining!==null&&$remaining<=3?'warning':'success'),'remaining'=>$remaining],
   'schedule'=>['level'=>$seconds===0?'danger':($seconds!==null&&$seconds<=3600?'warning':'success'),'seconds'=>$seconds,'restricted'=>$rules['schedule']!==false,'today'=>$rules['schedule']===false?null:$rules['schedule'][(int)$now->format('N')]],
   'browser'=>['level'=>$browserOk?'success':'danger','required'=>$rules['browser']],
  ];
  return ['indicators'=>$indicators,'allowed'=>!$reasons,'reasons'=>$reasons,'used'=>$count,'limit'=>$rules['limit'],'browser_required'=>$rules['browser'],'browser_state'=>$browser['state']??null,'browser_registration_available'=>$registrationAvailable,'browser_linked'=>$linked,'next'=>$next];
 }
 public function generate(int $actor,array $input,string $token,string $session):array {
  $id=Rules::text($input['id']??'',8192);$secure=Rules::text($input['secure']??'',8192);if(preg_match('/[;\r\n]/',$id.$secure))throw new HttpException(422,'ID y secure no válidos.');$key=$input['request_key']??'';if(!is_string($key)||!preg_match('/^[a-f0-9]{32}$/D',$key))throw new HttpException(422,'Solicitud no válida.');
  $this->db->begin_transaction();try{(new PermissionRepository($this->db))->lock();$status=$this->status($actor,$token,$session);if(!$status['allowed'])throw new HttpException(403,implode(' ',$status['reasons']));
   if($this->db->execute_query('SELECT id FROM fm_external_attempts WHERE user_id=? AND request_key=?',[$actor,$key])->fetch_assoc())throw new HttpException(409,'Este intento ya fue registrado. No se enviará otra solicitud.');
   $this->db->execute_query('INSERT INTO fm_external_attempts(user_id,request_key,created_at) VALUES(?,?,(UTC_TIMESTAMP() - INTERVAL 5 HOUR))',[$actor,$key]);$attempt=(int)$this->db->insert_id;$this->db->commit();
  }catch(\Throwable $e){$this->db->rollback();throw $e;}
  try{$result=($this->generator??new LinkGenerator())->generate($id,$secure);$success=true;}catch(\Throwable $e){$result=[];$success=false;}
  $this->db->execute_query('UPDATE fm_external_attempts SET success=?,finished=1 WHERE id=?',[(int)$success,$attempt]);
  return ['generated'=>$success,'message'=>$success?'Link generado.':'No se obtuvo un link. El intento quedó registrado.']+$result;
 }
 public function pendingBrowserCount(int $actor):int {
  $user=$this->authorize($actor,'external.restrictions');
  if((int)$user['rol_id']!==1)throw new HttpException(403,'Solo el administrador puede consultar este resumen.');
  return (int)$this->db->query("SELECT COUNT(*) n FROM fm_external_browsers WHERE state='pending'")->fetch_assoc()['n'];
 }
 public function report(int $actor,array $input,bool $dashboard=false):array {
  $u=$this->authorize($actor,$dashboard?'services.external_links':'reports.external_links');[$from,$to]=SpotifySalesRepository::period($input);$args=[$from.' 00:00:00',(new \DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d').' 00:00:00'];$where='created_at>=? AND created_at<?';if((int)$u['rol_id']!==1){$where.=' AND user_id=?';$args[]=$actor;}
  $q=Rules::text($input['q']??'',100,false);if($q!==''){$ids=[];foreach(UserNames::all($this->db) as $id=>$name)if(mb_stripos($name['display_name'],$q)!==false)$ids[]=(int)$id;$where.=' AND user_id IN ('.implode(',',$ids?:[0]).')';}
  $total=(int)$this->db->execute_query('SELECT COUNT(*) n FROM fm_external_attempts WHERE '.$where,$args)->fetch_assoc()['n'];$size=(int)($input['size']??20);if(!in_array($size,[10,20,50],true))throw new HttpException(422,'Tamaño no válido.');$pages=max(1,(int)ceil($total/$size));$page=max(1,min($pages,(int)($input['page']??1)));
  $rows=$this->db->execute_query('SELECT id,user_id,success,finished,created_at FROM fm_external_attempts WHERE '.$where.' ORDER BY id DESC LIMIT '.$size.' OFFSET '.(($page-1)*$size),$args)->fetch_all(MYSQLI_ASSOC);$names=UserNames::all($this->db);foreach($rows as &$r)$r['name']=$names[$r['user_id']]['display_name']??'Usuario no disponible';unset($r);
  $daily=$this->db->execute_query('SELECT DATE(created_at) day,COUNT(*) total,SUM(success) generated FROM fm_external_attempts WHERE '.$where.' GROUP BY DATE(created_at) ORDER BY day',$args)->fetch_all(MYSQLI_ASSOC);
  return compact('rows','total','pages','page','from','to','daily');
 }
}
