<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/bootstrap/app.php';
use FMGlobal\Repositories\{SpotifyMigration,SpotifyRepository,PermissionMigration,PermissionRepository,UserRepository,ScheduleRepository};
use FMGlobal\Services\Spotify\Rules;
use FMGlobal\Services\Links\AccountVault;
use FMGlobal\Services\Users\UserService;
use FMGlobal\Http\HttpException;
$config=require FM_ROOT.'/config/database.php';
if(!in_array($config['host'],['localhost','127.0.0.1'],true)||!str_ends_with($config['database'],'_local'))throw new RuntimeException('Solo pruebas locales.');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new mysqli($config['host'],$config['user'],$config['password']);$source=$config['database'];if(!preg_match('/^[a-zA-Z0-9_]+$/D',$source))throw new RuntimeException('Base no válida.');$name='fm_spotify_test_'.bin2hex(random_bytes(5));$count=0;
function spCheck(bool $ok,string $message):void{global $count;if(!$ok)throw new RuntimeException($message);$count++;}
function spReject(callable $fn,int $status,string $message):void{try{$fn();}catch(HttpException $e){spCheck($e->status===$status,$message.' ('.$e->status.')');return;}throw new RuntimeException($message);}
$week=array_fill(1,7,['mode'=>'off','slots'=>[]]);
$week[1]=['mode'=>'hours','slots'=>[['start'=>'09:00','end'=>'12:00'],['start'=>'12:00','end'=>'13:00']]];
$at=fn($time)=>new \DateTimeImmutable('2026-09-21 '.$time,new \DateTimeZone('America/Lima'));
spCheck(\FMGlobal\Services\Schedules\WeeklySchedule::remainingSeconds($week,$at('12:00'))===3600,'Una hora restante');
spCheck(\FMGlobal\Services\Schedules\WeeklySchedule::remainingSeconds($week,$at('11:59'))===3660,'Tramos consecutivos mantienen disponibilidad');
spCheck(\FMGlobal\Services\Schedules\WeeklySchedule::remainingSeconds($week,$at('13:00'))===0,'Cierre exacto del horario');
$week[1]=['mode'=>'hours','slots'=>[['start'=>'22:00','end'=>'24:00']]];
$week[2]=['mode'=>'hours','slots'=>[['start'=>'00:00','end'=>'02:00']]];
spCheck(\FMGlobal\Services\Schedules\WeeklySchedule::remainingSeconds($week,$at('23:30'))===9000,'Continuidad a medianoche');
spCheck(\FMGlobal\Services\Schedules\WeeklySchedule::remainingSeconds(array_fill(1,7,['mode'=>'all','slots'=>[]]),$at('23:30'))===null,'Semana completa no vence');
try {
 $db->query("CREATE DATABASE `$name` CHARACTER SET utf8mb4");foreach(['usuarios','personal','activacion','rol'] as $t)$db->query("CREATE TABLE `$name`.`$t` LIKE `$source`.`$t`");$db->select_db($name);$db->set_charset('utf8mb4');
 $db->query("INSERT INTO rol VALUES(1,'Administrador'),(2,'Asesor'),(3,'Soporte')");PermissionMigration::apply($db);SpotifyMigration::apply($db);SpotifyMigration::apply($db);
 $serviceId=(int)$db->query("SELECT id FROM fm_service_types WHERE code='spotify'")->fetch_assoc()['id'];
 spCheck((int)$db->query('SELECT COUNT(*) n FROM fm_service_types')->fetch_assoc()['n']===1,'Migración repetible');
 $users=new UserService(new UserRepository($db),new ScheduleRepository($db));$seed=fn($u,$r)=>['usuario'=>$u,'nombre'=>'Persona '.$u,'apellido_paterno'=>'Prueba','rol'=>(string)$r,'clave'=>'SyntheticTestOnly123!'];
 $admin=$users->save($seed('admin',1),'test',0);$advisor=$users->save($seed('asesor_sp',2),'test',$admin);$other=$users->save($seed('soporte_sp',3),'test',$admin);
 $permissions=new PermissionRepository($db);spCheck(!empty($permissions->effective($admin)['services.spotify'])&&empty($permissions->effective($advisor)['services.spotify']),'Permiso solo administrador por defecto');
 $testKey=random_bytes(32);$repo=new SpotifyRepository($db,new AccountVault($testKey));$op=fn($actor,$action,$data)=>$repo->execute($actor,$action,$data+['request_key'=>bin2hex(random_bytes(16))]);
 spReject(fn()=>$repo->listing($advisor,[]),403,'Sin permiso no lista');
 foreach([$advisor,$other] as $uid)$db->execute_query("INSERT INTO fm_user_permissions VALUES(?,'services.spotify',1)",[$uid]);

 \FMGlobal\Repositories\ExternalLinkMigration::apply($db);\FMGlobal\Repositories\ExternalLinkMigration::apply($db);
 $external=new \FMGlobal\Repositories\ExternalLinkRepository($db,new \FMGlobal\Services\Links\LinkGenerator(fn($payload)=>['status'=>200,'body'=>json_encode(['status'=>'success','login_url'=>'https://example.test/link'])]));
 $role=(int)$db->query("SELECT rol_id FROM rol WHERE rol_nombre='Externo'")->fetch_assoc()['rol_id'];$ext=$users->save($seed('externo',$role),'test',$admin);
 spCheck(!empty($permissions->effective($ext)['services.external_links'])&&empty($permissions->effective($ext)['reports.external_links']),'Externo recibe solo servicio');
 spReject(fn()=>$external->report($ext,[]),403,'Reporte externo requiere permiso propio');
 $token=str_repeat('a',64);$session='session-a';$payload=['id'=>'test-id','secure'=>'test-secure','request_key'=>bin2hex(random_bytes(16))];
 $config=$external->configuration($admin,'role',$role);$external->save($admin,['type'=>'role','id'=>$role,'revision'=>$config['revision'],'settings'=>['limit'=>2,'browser'=>true,'schedule'=>false]]);
 spCheck(!$external->status($ext,$token,$session)['allowed'],'Navegador sin autorización bloqueado');
 $indicators=$external->status($ext,$token,$session)['indicators'];spCheck($indicators['attempts']['level']==='warning'&&$indicators['browser']['level']==='danger'&&$indicators['schedule']['level']==='success','Colores de cupo reducido, navegador bloqueado y horario libre');
 $external->requestBrowser($ext,$token,'Navegador de prueba');$pending=$external->configuration($admin,'user',$ext)['browsers'][0];
 $external->browserAction($admin,['operation'=>'approve','user_id'=>$ext,'browser_id'=>$pending['id']]);
 spCheck($external->status($ext,$token,$session,true)['allowed'],'Navegador aprobado habilita sesión');
 spCheck(!$external->status($ext,str_repeat('b',64),'other',true)['allowed'],'Otro navegador no obtiene sesión operativa');
 spCheck($external->status($ext,$token,$session)['allowed'],'Intento ajeno no expulsa navegador autorizado');
 $result=$external->generate($ext,$payload,$token,$session);spCheck($result['generated'],'Generación externa exitosa');
 spReject(fn()=>$external->generate($ext,$payload,$token,$session),409,'Reintento no duplica llamada');
 $failed=new \FMGlobal\Repositories\ExternalLinkRepository($db,new \FMGlobal\Services\Links\LinkGenerator(fn($payload)=>['status'=>500,'body'=>'']));$payload['request_key']=bin2hex(random_bytes(16));spCheck(!$failed->generate($ext,$payload,$token,$session)['generated'],'Fallo consume intento');
 spCheck(!$external->status($ext,$token,$session)['allowed'],'Cupo diario agotado');$payload['request_key']=bin2hex(random_bytes(16));spReject(fn()=>$external->generate($ext,$payload,$token,$session),403,'Límite servidor impide exceder cupo');
 spCheck($external->status($ext,$token,$session)['indicators']['attempts']['level']==='danger','Cupo agotado rojo');
 spCheck($external->report($ext,[],true)['total']===2,'Resumen propio incluye fallos');spCheck($external->report($admin,[])['total']===2,'Admin ve historial global');
 spCheck(!str_contains(json_encode($db->query('SELECT * FROM fm_external_attempts')->fetch_all(MYSQLI_ASSOC)),'test-secure'),'Intentos no almacenan credenciales');
 $config=$external->configuration($admin,'user',$ext);$external->save($admin,['type'=>'user','id'=>$ext,'revision'=>$config['revision'],'settings'=>['limit'=>3,'browser'=>false]]);spCheck($external->status($ext,'different','different')['allowed'],'Excepción usuario reemplaza cupo y navegador');
 $off=array_fill(1,7,['mode'=>'off','slots'=>[]]);$config=$external->configuration($admin,'user',$ext);$external->save($admin,['type'=>'user','id'=>$ext,'revision'=>$config['revision'],'settings'=>['schedule'=>$off,'limit'=>null,'browser'=>false]]);spCheck(!$external->status($ext,'different','different')['allowed'],'Horario bloquea aun sin navegador');spCheck($external->effective($ext)['limit']===2,'Cupo vuelve al perfil');
 $external->browserAction($admin,['operation'=>'reset','user_id'=>$ext]);spCheck(!$external->configuration($admin,'user',$ext)['browsers'],'Reset revoca navegadores');
 $externalOther=$users->save($seed('externo_otro',$role),'test',$admin);
 $initial=$external->status($externalOther,$token,'first',true);
 spCheck(!$initial['allowed']&&$initial['browser_registration_available']&&!$initial['browser_linked'],'Primera visita ofrece registrar sin afirmar otro navegador');
 $external->registerBrowser($externalOther,$token,'first','Primer navegador');
 $first=$external->status($externalOther,$token,'first',true);
 spCheck($first['browser_state']==='approved'&&$first['allowed'],'Primer navegador registrado sin aprobación');
 spCheck($external->status($externalOther,$token,'first',true)['allowed'],'Recarga conserva navegador inicial');
 $replacement=str_repeat('c',64);
 spCheck(!$external->status($externalOther,$replacement,'second',true)['allowed'],'Segundo navegador no se vincula automáticamente');
 spReject(fn()=>$external->registerBrowser($externalOther,$replacement,'second','Segundo navegador'),409,'Registro no reemplaza navegador existente');
 spReject(fn()=>$external->generate($externalOther,$payload,$replacement,'second'),403,'Navegador sin vincular no genera');
 $external->requestBrowser($externalOther,$replacement,'Segundo navegador');
 spCheck($external->pendingBrowserCount($admin)>0,'Resumen administrativo cuenta solicitudes pendientes');
 spReject(fn()=>$external->pendingBrowserCount($externalOther),403,'Usuario operativo no consulta pendientes globales');
 spCheck($external->status($externalOther,$replacement,'second',true)['browser_state']==='pending','Cambio queda pendiente');
 spCheck($external->status($externalOther,$token,'first')['allowed'],'Solicitud de cambio conserva el anterior');
 $pendingChange=$db->execute_query("SELECT id FROM fm_external_browsers WHERE user_id=? AND state='pending'",[$externalOther])->fetch_assoc();
 $external->browserAction($admin,['operation'=>'approve','user_id'=>$externalOther,'browser_id'=>$pendingChange['id']]);
 spCheck($external->status($externalOther,$replacement,'second',true)['allowed'],'Cambio autorizado habilita nuevo navegador');
 spCheck(!$external->status($externalOther,$token,'first',true)['allowed'],'Anterior no se revincula tras el cambio');
 spCheck((int)$db->execute_query("SELECT COUNT(*) n FROM fm_external_browsers WHERE user_id=? AND state='approved'",[$externalOther])->fetch_assoc()['n']===1,'Un único navegador autorizado');
 $external->requestBrowser($externalOther,str_repeat('d',64),'Solicitud pendiente antes del reset');
 $external->browserAction($admin,['operation'=>'reset','user_id'=>$externalOther]);
 spCheck((int)$db->execute_query('SELECT COUNT(*) n FROM fm_external_browsers WHERE user_id=?',[$externalOther])->fetch_assoc()['n']===0,'Restablecer elimina autorizados, revocados y pendientes del usuario');
 $reset=$external->status($externalOther,$replacement,'second');spCheck(!$reset['browser_linked']&&$reset['browser_registration_available'],'Restablecer permite registrar como primer acceso');
 $external->registerBrowser($externalOther,$replacement,'second','Navegador tras restablecer');
 spCheck($external->status($externalOther,$replacement,'second',true)['allowed'],'Registro tras restablecer no requiere aprobación');
 spCheck(!$external->status($externalOther,$token,'first',true)['allowed'],'Otros navegadores anteriores siguen sin acceso tras el nuevo registro');
 spReject(fn()=>$external->registerBrowser($externalOther,$token,'first','Anterior'),409,'Restablecer solo permite un primer registro');
 spCheck((int)$db->execute_query("SELECT COUNT(*) n FROM fm_external_audit WHERE target_type='user' AND target_id=? AND action='reset'",[$externalOther])->fetch_assoc()['n']===1,'Restablecer conserva auditoría de la acción');
spCheck($external->report($externalOther,[],true)['total']===0,'Externo no ve intentos de otro usuario');
 $db->execute_query("INSERT INTO fm_user_permissions VALUES(?,'reports.external_links',1)",[$externalOther]);spCheck($external->report($externalOther,['user_id'=>$ext])['total']===0,'Reporte ignora intento de ampliar usuario');
 spReject(fn()=>$external->configuration($ext,'user',$ext),403,'Usuario externo no administra sus restricciones');
 // Cupo concurrente: queda exactamente un intento disponible.
 $config=$external->configuration($admin,'user',$ext);$external->save($admin,['type'=>'user','id'=>$ext,'revision'=>$config['revision'],'settings'=>['schedule'=>false,'limit'=>3,'browser'=>false]]);
 $workerConfig=(static function(){return require FM_ROOT.'/config/database.php';})();$workerEnv=getenv();$workerEnv['FMGLOBAL_DB_NAME']=$name;$workerEnv['FMGLOBAL_DB_HOST']=$workerConfig['host'];$workerEnv['FMGLOBAL_DB_USER']=$workerConfig['user'];$workerEnv['FMGLOBAL_DB_PASSWORD']=$workerConfig['password'];$workers=[];
 try{foreach([1,2] as $i){$proc=proc_open([PHP_BINARY,FM_ROOT.'/tests/helpers/external-generate.php',(string)$ext],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,FM_ROOT,$workerEnv);if(!is_resource($proc))throw new RuntimeException('No inicia proceso');fclose($pipes[0]);$workers[]=[$proc,$pipes];}
 $statuses=[];foreach($workers as [$proc,$pipes]){$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);spCheck(proc_close($proc)===0,'Proceso externo concurrente: '.$err);$statuses[]=json_decode($out,true,512,JSON_THROW_ON_ERROR)['status'];}sort($statuses);spCheck($statuses===[200,403],'Solo una llamada usa último intento concurrente');
 }finally{foreach($workers as [$proc])if(is_resource($proc)){proc_terminate($proc);proc_close($proc);}}
 $db->execute_query('UPDATE fm_external_attempts SET created_at=DATE(UTC_TIMESTAMP() - INTERVAL 5 HOUR)-INTERVAL 1 SECOND WHERE user_id=?',[$ext]);spCheck($external->status($ext,'any','any')['used']===0,'Intentos del día anterior no consumen cupo de hoy');
 // Spotify: meses, cliente opcional, beneficiarios y caídas persistentes.
 $main=['service_id'=>$serviceId,'email'=>'evolution@example.test','payment_email'=>'pay@example.test','next_payment'=>'2026-12-01','accounts'=>[['email'=>'child1@example.test','password'=>'test-password'],['email'=>'child2@example.test','password'=>'test-password']]];$mid=$op($admin,'save',$main)['id'];
 $obtain=['service_id'=>$serviceId,'advisor_id'=>$advisor,'phone'=>'','client_name'=>'','start_date'=>'2026-01-31','months'=>3];$assigned=$op($admin,'obtain',$obtain);$row=$repo->listing($advisor,[])['rows'][0];
 spCheck($row['phone']===null&&$row['end_date']==='2026-04-30','Cliente opcional y meses al afiliar');
 spReject(fn()=>$op($advisor,'renew',['assignment_id'=>$row['assignment_id'],'revision'=>$row['revision'],'months'=>0]),422,'Rechaza cero meses');
 $op($advisor,'renew',['assignment_id'=>$row['assignment_id'],'revision'=>$row['revision'],'months'=>2]);$row=$repo->listing($advisor,[])['rows'][0];spCheck($row['end_date']==='2026-06-30','Renueva varios meses desde vencimiento');
 $op($advisor,'transfer',['assignment_id'=>$row['assignment_id'],'revision'=>$row['revision'],'phone'=>'+51988888888','client_name'=>'Cliente familia','beneficiary'=>'mamá']);$row=$repo->listing($advisor,[])['rows'][0];spCheck($row['beneficiary']==='mamá','Asesor vincula beneficiario');
 spReject(fn()=>$op($advisor,'transfer',['assignment_id'=>$row['assignment_id'],'revision'=>$row['revision'],'advisor_id'=>$other]),403,'Asesor no cambia asesor');
 $op($admin,'obtain',['service_id'=>$serviceId,'advisor_id'=>$advisor,'phone'=>'+51988888888','client_name'=>'Cliente familia','beneficiary'=>'mamá','start_date'=>'2026-02-01']);spCheck((int)$db->query('SELECT COUNT(*) n FROM fm_client_beneficiaries')->fetch_assoc()['n']===1,'Beneficiario reutilizable en varias cuentas');
 $op($advisor,'fall',['assignment_id'=>$row['assignment_id'],'revision'=>$row['revision'],'account_revision'=>$row['account_revision']]);$fallen=$repo->listing($advisor,['state'=>'fallen'])['rows'][0];spCheck($fallen['phone']==='+51988888888'&&$fallen['end_date']==='2026-06-30','Caída conserva cliente y fechas visible al asesor');
 $result=$op($admin,'transfer_bulk',['source_id'=>$advisor,'advisor_id'=>$other]);spCheck($repo->listing($advisor,[])['total']===0&&$repo->listing($other,[])['total']===2,'Traslado masivo incluye caídas');
 $fallen=$repo->listing($admin,['state'=>'fallen'])['rows'][0];$op($admin,'rehabilitate',['account_id'=>$fallen['account_id'],'account_revision'=>$fallen['account_revision'],'email'=>'replaced@example.test','profile_id'=>$fallen['profile_id'],'profile_name'=>'Perfil actualizado','password'=>'replacement']);
 $restored=$repo->listing($other,['q'=>'replaced@example.test'])['rows'][0];spCheck($restored['phone']==='+51988888888'&&$restored['end_date']==='2026-06-30'&&$restored['state']==='enabled','Restauración cambia correo conservando asignación');
 $notifications=$repo->notifications($other);spCheck(count($notifications)===1&&count($repo->notifications($advisor))===0,'Notifica al responsable actual');$op($other,'read_notification',['id'=>$notifications[0]['id']]);spCheck(!$repo->notifications($other),'Notificación persiste hasta lectura');
 // Edición de cliente conserva sus otras cuentas y beneficiarios.
 $editRow=$repo->listing($other,['q'=>'replaced@example.test'])['rows'][0];
 $op($other,'transfer',['assignment_id'=>$editRow['assignment_id'],'revision'=>$editRow['revision'],'client_mode'=>'edit','original_client_name'=>'Cliente familia','phone'=>'+51999999999','client_name'=>'Familia actualizada','beneficiary'=>'mamá']);
 spCheck($repo->listing($other,[])['rows'][0]['phone']==='+51999999999','Edición celular en cascada conserva cuentas');
 spCheck($repo->client($other,'+51999999999')['beneficiaries'][0]['label']==='mamá','Beneficiario conserva cliente al cambiar celular');
 $cl=new \FMGlobal\Repositories\ClientsRepository($db);$cl->save($admin,['name'=>'Otro cliente','phone'=>'+12025550123']);$editRow=$repo->listing($other,['q'=>'replaced@example.test'])['rows'][0];
 spReject(fn()=>$op($other,'transfer',['assignment_id'=>$editRow['assignment_id'],'revision'=>$editRow['revision'],'client_mode'=>'edit','original_client_name'=>'Familia actualizada','phone'=>'+12025550123','client_name'=>'Colisión']),409,'Edición no combina clientes con celular duplicado');
 spReject(fn()=>$op($advisor,'transfer',['assignment_id'=>$editRow['assignment_id'],'revision'=>$editRow['revision'],'phone'=>'','client_name'=>'']),404,'No edita cliente de asignación ajena');
 // Migración legacy: caída antigua no debe quedar sin responsable.
 $account=$editRow['account_id'];$assignment=$editRow['assignment_id'];$db->execute_query("UPDATE fm_service_accounts SET state='fallen' WHERE id=?",[$account]);$db->execute_query("UPDATE fm_service_assignments SET closed_at=NOW(),close_reason='fallen' WHERE id=?",[$assignment]);$db->execute_query('UPDATE fm_service_profiles SET current_assignment_id=NULL WHERE id=?',[$editRow['profile_id']]);$db->query("DELETE FROM fm_migrations WHERE name='008_spotify_evolution'");\FMGlobal\Repositories\SpotifyEvolutionMigration::apply($db);\FMGlobal\Repositories\SpotifyEvolutionMigration::apply($db);spCheck($repo->listing($other,['q'=>'replaced@example.test'])['rows'][0]['assignment_id']===$assignment,'Migra caída antigua de forma repetible');
 echo "$count comprobaciones de evolución y generador externo correctas.\n";
}finally{$db->query("DROP DATABASE IF EXISTS `$name`");$db->close();}
