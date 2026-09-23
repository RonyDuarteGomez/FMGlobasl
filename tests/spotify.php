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
try {
 $db->query("CREATE DATABASE `$name` CHARACTER SET utf8mb4");foreach(['usuarios','personal','activacion','rol'] as $t)$db->query("CREATE TABLE `$name`.`$t` LIKE `$source`.`$t`");$db->select_db($name);$db->set_charset('utf8mb4');
 $db->query("INSERT INTO rol VALUES(1,'Administrador'),(2,'Asesor'),(3,'Soporte')");PermissionMigration::apply($db);SpotifyMigration::apply($db);SpotifyMigration::apply($db);
 $serviceId=(int)$db->query("SELECT id FROM fm_service_types WHERE code='spotify'")->fetch_assoc()['id'];
 spCheck((int)$db->query('SELECT COUNT(*) n FROM fm_service_types')->fetch_assoc()['n']===1,'Migración repetible');
 $users=new UserService(new UserRepository($db),new ScheduleRepository($db));$seed=fn($u,$r)=>['usuario'=>$u,'nombre'=>'Persona '.$u,'apellido_paterno'=>'Prueba','rol'=>(string)$r,'clave'=>'Synthetic test only 123!'];
 $admin=$users->save($seed('admin',1),'test',0);$advisor=$users->save($seed('asesor_sp',2),'test',$admin);$other=$users->save($seed('soporte_sp',3),'test',$admin);
 $permissions=new PermissionRepository($db);spCheck(!empty($permissions->effective($admin)['services.spotify'])&&empty($permissions->effective($advisor)['services.spotify']),'Permiso solo administrador por defecto');
 $testKey=random_bytes(32);$repo=new SpotifyRepository($db,new AccountVault($testKey));$op=fn($actor,$action,$data)=>$repo->execute($actor,$action,$data+['request_key'=>bin2hex(random_bytes(16))]);
 spReject(fn()=>$repo->listing($advisor,[]),403,'Sin permiso no lista');
 foreach([$advisor,$other] as $uid)$db->execute_query("INSERT INTO fm_user_permissions VALUES(?,'services.spotify',1)",[$uid]);
 spCheck(Rules::month('2027-01-31')==='2027-02-28'&&Rules::month('2027-02-28')==='2027-03-28'&&Rules::month('2028-01-31')==='2028-02-29','Meses cortos y bisiesto');
 spCheck(Rules::phone('+51 (987) 654-321')==='+51987654321','Normaliza internacional sin adivinar país');spReject(fn()=>Rules::phone('987654321'),422,'Exige prefijo internacional');spReject(fn()=>Rules::date('2026-02-30'),422,'Fecha inexistente');
 $main=['service_id'=>$serviceId,'email'=>'principal@example.test','payment_email'=>'pago@example.test','next_payment'=>date('Y-m-d',strtotime('+20 days')),'accounts'=>[['email'=>'one@example.test','password'=>'synthetic-one','profiles'=>[['name'=>'']]],['email'=>'two@example.test','password'=>'synthetic-two','profiles'=>[['name'=>'Perfil Dos']]]]];
 spReject(fn()=>$op($advisor,'save',$main),403,'Solo admin crea inventario');$result=$op($admin,'save',$main);$mainId=$result['id'];
 spCheck($repo->main($admin,$mainId)['accounts'][0]['profiles'][0]['name']==='Perfil 1','Perfil predeterminado');
 spCheck($repo->metadata($advisor)['services'][0]['available']===2&&$repo->listing($advisor,[])['total']===0,'Operativo ve cantidad pero no inventario libre');
 spCheck($repo->metadata($advisor)['users']===[],'No entrega listado global de usuarios');spReject(fn()=>$repo->main($advisor,$mainId),403,'No lee información de proveedor');
 spCheck(!str_contains(json_encode($db->query('SELECT * FROM fm_service_accounts')->fetch_all(MYSQLI_ASSOC)),'synthetic-one'),'Contraseñas cifradas');
 spReject(fn()=>$op($admin,'save',$main),409,'Duplicados rechazados');spCheck($repo->listing($admin,[])['total']===2,'Rollback conserva inventario');
 $tooMany=$main;$tooMany['email']='many@example.test';$tooMany['accounts']=array_fill(0,6,$main['accounts'][0]);spReject(fn()=>$op($admin,'save',$tooMany),422,'Límite cinco secundarias');
 $tooMany=$main;$tooMany['email']='profiles@example.test';$tooMany['accounts'][0]['profiles']=[['name'=>'1'],['name'=>'2']];spReject(fn()=>$op($admin,'save',$tooMany),422,'Límite un perfil');
 $obtain=['service_id'=>$serviceId,'phone'=>'+51987654321','client_name'=>'Cliente Uno','start_date'=>'2027-01-31'];
 spReject(fn()=>$op($admin,'obtain',$obtain+['advisor_id'=>$admin]),422,'TI no recibe asignaciones');
 $command=$obtain+['request_key'=>bin2hex(random_bytes(16))];$first=$repo->execute($advisor,'obtain',$command);spCheck($first['available']&&$repo->execute($advisor,'obtain',$command)===$first,'Reintento no entrega dos cuentas');
 $row=$repo->listing($advisor,[])['rows'][0];spCheck($row['end_date']==='2027-02-28','Inicio concede un mes');spCheck(!array_key_exists('main_email',$row)&&!array_key_exists('payment_days',$row)&&!array_key_exists('payment_email',$row),'Respuesta operativa excluye proveedor');
 spCheck($repo->listing($other,['advisor_id'=>$advisor])['total']===0,'Filtro manipulado no amplía alcance');spReject(fn()=>$repo->assignedDetails($other,$first['assignment_id']),404,'No obtiene credenciales ajenas');
 $rev=['assignment_id'=>$row['assignment_id'],'revision'=>$row['revision'],'account_revision'=>$row['account_revision']];spReject(fn()=>$op($other,'renew',$rev),404,'No renueva cuentas ajenas');
 $renew=$rev+['request_key'=>bin2hex(random_bytes(16))];$repo->execute($advisor,'renew',$renew);$repo->execute($advisor,'renew',$renew);spCheck((int)$db->query('SELECT COUNT(*) n FROM fm_service_renewals')->fetch_assoc()['n']===1,'Renovación idempotente');
 $row=$repo->listing($advisor,[])['rows'][0];spCheck($row['end_date']==='2027-03-28'&&$row['start_date']==='2027-01-31','Renueva desde vencimiento y conserva inicio');spReject(fn()=>$op($advisor,'renew',$rev),409,'Revisión impide doble renovación con comando distinto');
 $assignment2=$op($other,'obtain',array_replace($obtain,['phone'=>'+12025550123','client_name'=>'Cliente Dos']));spCheck($assignment2['available']&&$repo->metadata($advisor)['services'][0]['available']===0,'Segundo espacio diferente');
 $noStock=$op($advisor,'obtain',array_replace($obtain,['phone'=>'+442079460123','client_name'=>'Cliente Sin Stock']));spCheck(!$noStock['available']&&$repo->client($advisor,'+442079460123')['name']==='Cliente Sin Stock','Conserva cliente al agotarse stock');
 spCheck((int)$db->query('SELECT COUNT(*) n FROM fm_service_assignments')->fetch_assoc()['n']===2,'Sin stock no crea asignación');
 $rev=['assignment_id'=>$row['assignment_id'],'revision'=>$row['revision'],'account_revision'=>$row['account_revision']];
 spReject(fn()=>$op($advisor,'release',$rev),422,'Liberación exige contraseña');spReject(fn()=>$op($advisor,'release',$rev+['password'=>$row['password']]),422,'Exige contraseña diferente');
 $op($advisor,'release',$rev+['password'=>'synthetic-new','profile_name'=>'']);spCheck($repo->listing($advisor,[])['total']===0&&$repo->metadata($advisor)['services'][0]['available']===1,'Liberación quita acceso y devuelve disponibilidad');spReject(fn()=>$repo->assignedDetails($advisor,$first['assignment_id']),404,'No lee credenciales después de liberar');
 spCheck((int)$db->query('SELECT COUNT(*) n FROM fm_service_assignments WHERE closed_at IS NOT NULL')->fetch_assoc()['n']===1&&$repo->client($advisor,$obtain['phone'])!==null,'Conserva cliente e historial');
 $oldMain=$repo->main($admin,$mainId);$free=array_values(array_filter($repo->listing($admin,[])['rows'],fn($r)=>!$r['assignment_id']))[0];
 $next=$op($advisor,'obtain',$obtain);$details=$repo->assignedDetails($advisor,$next['assignment_id']);spCheck($details['password']==='synthetic-new','Reasignación recibe contraseña actualizada');
 $op($advisor,'credentials',['assignment_id'=>$details['id'],'revision'=>$details['revision'],'account_revision'=>$details['account_revision'],'password'=>'synthetic-updated','profile_name'=>'Perfil nuevo']);
 $edit=$oldMain;$edit['accounts'][0]['password']=$edit['accounts'][0]['password'];spReject(fn()=>$op($admin,'save',$edit),409,'Edición principal obsoleta no pisa contraseña operativa');
 $row=$repo->listing($advisor,[])['rows'][0];$op($advisor,'fall',['assignment_id'=>$row['assignment_id'],'revision'=>$row['revision'],'account_revision'=>$row['account_revision'],'reason'=>'No funciona']);
 spCheck($repo->listing($advisor,[])['total']===0&&$repo->metadata($advisor)['services'][0]['available']===0,'Caída cierra asignación y no queda disponible');
 $fallen=$repo->listing($admin,['state'=>'fallen'])['rows'][0];spCheck($fallen['fallen_reason']==='No funciona','Administrador recibe motivo');spCheck((int)$fallen['fallen_reporter_id']===$advisor&&$fallen['fallen_reporter_name']===\FMGlobal\Repositories\UserNames::all($db)[$advisor]['display_name'],'Caída conserva autor real por ID y muestra su nombre');$rehab=['account_id'=>$fallen['account_id'],'account_revision'=>$fallen['account_revision']];spReject(fn()=>$op($advisor,'rehabilitate',$rehab),403,'Solo admin habilita');$op($admin,'rehabilitate',$rehab);spCheck($repo->metadata($advisor)['services'][0]['available']===1,'Rehabilitación devuelve espacio sin restaurar cliente');
 $db->execute_query("UPDATE fm_user_permissions SET allowed=0 WHERE user_id=? AND permission_code='services.spotify'",[$other]);spReject(fn()=>$repo->listing($other,[]),403,'Revocación bloquea');spCheck($repo->metadata($admin)['orphaned']===1,'Alerta de asignaciones sin acceso');
 $orphan=array_values(array_filter($repo->listing($admin,[])['rows'],fn($r)=>(int)$r['advisor_id']===$other))[0];$op($admin,'transfer',['assignment_id'=>$orphan['assignment_id'],'revision'=>$orphan['revision'],'advisor_id'=>$advisor]);spCheck($repo->metadata($admin)['orphaned']===0&&$repo->listing($advisor,[])['total']===1,'Administrador traslada conservando cliente');
 $m=$repo->main($admin,$mainId);$m['next_payment']=date('Y-m-d',strtotime('-1 day'));$op($admin,'save',$m);spCheck($repo->metadata($advisor)['services'][0]['available']===1,'Pago vencido no excluye libres');
 spCheck($repo->listing($admin,['state'=>'available'])['total']===1,'Filtro disponible incluye pago vencido');
 spCheck(!str_contains(json_encode($db->query('SELECT * FROM fm_service_audit')->fetch_all(MYSQLI_ASSOC)),'synthetic'),'Auditoría sin contraseñas');
 $db->execute_query("UPDATE fm_user_permissions SET allowed=0 WHERE user_id=? AND permission_code='services.spotify'",[$advisor]);PermissionMigration::apply($db);spCheck(empty($permissions->effective($advisor)['services.spotify']),'Reejecución conserva denegaciones');
 // Dos procesos compiten por el único espacio libre.
 $db->execute_query("UPDATE fm_user_permissions SET allowed=1 WHERE user_id=? AND permission_code='services.spotify'",[$advisor]);
 $m=$repo->main($admin,$mainId);$m['next_payment']=date('Y-m-d',strtotime('-1 day'));$op($admin,'save',$m);
 spCheck($repo->metadata($admin)['services'][0]['available']===1,'Un espacio para prueba concurrente');
 $env=getenv();$env['FMGLOBAL_DB_NAME']=$name;$env['FM_SP_TEST_KEY']=bin2hex($testKey);$env['FMGLOBAL_DB_HOST']=$config['host'];$env['FMGLOBAL_DB_USER']=$config['user'];$env['FMGLOBAL_DB_PASSWORD']=$config['password'];$workers=[];
 try {
  foreach([1,2] as $n){$payload=['request_key'=>bin2hex(random_bytes(16)),'service_id'=>$serviceId,'phone'=>'+1202555090'.$n,'client_name'=>'Concurrente '.$n,'start_date'=>date('Y-m-d'),'advisor_id'=>$advisor];$proc=proc_open([PHP_BINARY,FM_ROOT.'/tests/helpers/spotify-obtain.php',(string)$admin,json_encode($payload)],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,FM_ROOT,$env);if(!is_resource($proc))throw new RuntimeException('No inicia prueba concurrente');fclose($pipes[0]);$workers[]=[$proc,$pipes];}
  $results=[];foreach($workers as [$proc,$pipes]){$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);spCheck(proc_close($proc)===0,'Proceso concurrente: '.$err);$results[]=json_decode($out,true,512,JSON_THROW_ON_ERROR);}
  spCheck(count(array_filter($results,fn($r)=>$r['available']))===1,'Solo una solicitud simultánea obtiene la cuenta, aunque el pago esté vencido');
 }finally{foreach($workers as [$proc])if(is_resource($proc)){proc_terminate($proc);proc_close($proc);}}
 // Una asignación vencida no se libera; renovar usa la fecha guardada.
 $expired=$repo->listing($advisor,[])['rows'][0];$db->execute_query("UPDATE fm_service_assignments SET end_date='2020-01-31' WHERE id=?",[$expired['assignment_id']]);
 spCheck($repo->listing($advisor,['expiry'=>'expired'])['total']>=1&&$repo->metadata($advisor)['services'][0]['available']===0,'Vencida sigue ocupada y visible');
 $op($advisor,'renew',['assignment_id'=>$expired['assignment_id'],'revision'=>$expired['revision']]);
 spCheck($db->execute_query('SELECT end_date FROM fm_service_assignments WHERE id=?',[$expired['assignment_id']])->fetch_assoc()['end_date']==='2020-02-29','Renovación vencida parte del vencimiento anterior');
 // Pago de proveedor: solo administrador, sin afectar asignaciones, con reintento seguro.
 $beforePayment=$repo->main($admin,$mainId);$beforeAssignments=$db->query('SELECT * FROM fm_service_assignments ORDER BY id')->fetch_all(MYSQLI_ASSOC);
 $payment=['main_id'=>$mainId,'main_revision'=>$beforePayment['revision'],'request_key'=>bin2hex(random_bytes(16))];
 spReject(fn()=>$repo->execute($advisor,'pay',$payment),403,'Solo administrador registra pago');
 $paid=$repo->execute($admin,'pay',$payment);spCheck($repo->execute($admin,'pay',$payment)===$paid,'Pago idempotente');
 spCheck($repo->main($admin,$mainId)['next_payment']===Rules::month($beforePayment['next_payment']),'Pago avanza un mes desde fecha guardada');
 spCheck($beforeAssignments===$db->query('SELECT * FROM fm_service_assignments ORDER BY id')->fetch_all(MYSQLI_ASSOC),'Pago no modifica asignaciones');
 spReject(fn()=>$op($admin,'pay',['main_id'=>$mainId,'main_revision'=>$beforePayment['revision']]),409,'Evita pago desde versión obsoleta');
 spCheck((int)$db->query("SELECT COUNT(*) n FROM fm_service_audit WHERE action='provider_payment'")->fetch_assoc()['n']===1,'Pago auditado una vez');
 spReject(fn()=>$repo->payments($advisor,[]),403,'Operativo no consulta pagos');
 $paymentListing=$repo->payments($admin,[]);spCheck($paymentListing['total']===1&&(int)$paymentListing['rows'][0]['summary']['total']===2,'Pagos tiene una fila por principal y cuenta secundarias');
 $latest=$repo->main($admin,$mainId);$bulk=['items'=>[['main_id'=>$mainId,'main_revision'=>$latest['revision']]],'request_key'=>bin2hex(random_bytes(16))];
 $bulkResult=$repo->execute($admin,'pay_bulk',$bulk);spCheck($repo->execute($admin,'pay_bulk',$bulk)===$bulkResult,'Pago en bloque idempotente');
 spCheck((int)$db->query('SELECT COUNT(*) n FROM fm_service_payments')->fetch_assoc()['n']===2,'Historial de pagos individuales y masivos');
 $latest=$repo->main($admin,$mainId);$historyCount=(int)$db->query('SELECT COUNT(*) n FROM fm_service_payments')->fetch_assoc()['n'];
 spReject(fn()=>$op($admin,'pay_bulk',['items'=>[['main_id'=>$mainId,'main_revision'=>$latest['revision']],['main_id'=>999999,'main_revision'=>1]]]),404,'Pago en bloque rechaza cuenta inexistente');
 spCheck($repo->main($admin,$mainId)['next_payment']===$latest['next_payment']&&(int)$db->query('SELECT COUNT(*) n FROM fm_service_payments')->fetch_assoc()['n']===$historyCount,'Fallo revierte todo el bloque y su historial');
 spReject(fn()=>$op($admin,'pay_bulk',['items'=>[['main_id'=>$mainId,'main_revision'=>$latest['revision']],['main_id'=>$mainId,'main_revision'=>$latest['revision']]]]),422,'Bloque no repite principal');
 foreach(['expired'=>-1,'soon'=>3,'current'=>4] as $filter=>$days){$db->execute_query('UPDATE fm_service_mains SET next_payment=? WHERE id=?',[date('Y-m-d',strtotime(($days>=0?'+':'').$days.' days')),$mainId]);spCheck($repo->payments($admin,['state'=>$filter])['total']===1,'Filtro de pago '.$filter);foreach(array_diff(['expired','soon','current'],[$filter]) as $excluded)spCheck($repo->payments($admin,['state'=>$excluded])['total']===0,'Exclusión filtro '.$excluded);}
 // Importación CSV real en la base temporal.
 $header=\FMGlobal\Services\Spotify\CsvImport::HEADER;
 $csvRow=['Spotify','csv-main@example.test','pay@example.test','2027-10-20','csv-one@example.test','private-csv-password','','habilitada','','','','','',''];
 $makeCsv=function(array $records)use($header):string{$f=fopen('php://temp','r+');foreach(array_merge([$header],$records) as $r)fputcsv($f,$r,',','"','');rewind($f);$text=stream_get_contents($f);fclose($f);return $text;};
 $csv=$makeCsv([$csvRow]);$csvKey=bin2hex(random_bytes(16));
 spReject(fn()=>$repo->importFile($advisor,$csv,$csvKey),403,'Operativo no importa CSV');
 $import=$repo->importFile($admin,$csv,$csvKey);spCheck($import['loaded']===1&&!$import['errors'],'Carga cuenta libre');
 spCheck($repo->importFile($admin,$csv,$csvKey)===$import,'Reenvío de importación idempotente');
 $import=$repo->importFile($admin,$csv,bin2hex(random_bytes(16)));spCheck($import['loaded']===0&&count($import['errors'])===1,'Duplicado no sobrescribe');
 $assigned=$csvRow;$assigned[4]='csv-assigned@example.test';$assigned[8]='asesor_sp';$assigned[9]='Cliente CSV';$assigned[10]='+12025550777';$assigned[11]='2026-01-01';$assigned[12]='2026-02-01';
 $bad=$csvRow;$bad[4]='csv-bad@example.test';$bad[3]='2026-02-30';
 $fallen=$csvRow;$fallen[4]='csv-fallen@example.test';$fallen[7]='caida';
 $import=$repo->importFile($admin,$makeCsv([$assigned,$bad,$fallen]),bin2hex(random_bytes(16)));spCheck($import['loaded']===2&&count($import['errors'])===1,'Carga parcial de asignada y caída, rechaza fecha inválida');
 $a=$db->query("SELECT a.* FROM fm_service_assignments a JOIN fm_service_profiles p ON p.id=a.profile_id JOIN fm_service_accounts c ON c.id=p.account_id WHERE c.email='csv-assigned@example.test'")->fetch_assoc();spCheck((int)$a['advisor_id']===$advisor&&$a['end_date']==='2026-02-01','Asigna al ID y conserva vencimiento histórico');
 $extra=[];foreach(range(1,3) as $n){$r=$csvRow;$r[4]='csv-extra'.$n.'@example.test';$extra[]=$r;}
 $import=$repo->importFile($admin,$makeCsv($extra),bin2hex(random_bytes(16)));spCheck($import['loaded']===2&&count($import['errors'])===1,'Máximo cinco secundarias también en CSV');
 $conflict=$csvRow;$conflict[4]='csv-conflict@example.test';$conflict[2]='different@example.test';$import=$repo->importFile($admin,$makeCsv([$conflict]),bin2hex(random_bytes(16)));spCheck($import['loaded']===0,'No modifica datos de principal existente');
 $adminRow=$assigned;$adminRow[1]='another@example.test';$adminRow[4]='csv-admin@example.test';$adminRow[8]='admin';$import=$repo->importFile($admin,$makeCsv([$adminRow]),bin2hex(random_bytes(16)));spCheck($import['loaded']===0,'Admin TI excluido de carga');
 spCheck((int)$db->query("SELECT COUNT(*) n FROM fm_service_mains WHERE email='another@example.test'")->fetch_assoc()['n']===0,'Fila rechazada no deja principal huérfana');
 spReject(fn()=>\FMGlobal\Services\Spotify\CsvImport::parse('cabecera incorrecta'),422,'Rechaza formato incorrecto antes de importar');
 spCheck(count(\FMGlobal\Services\Spotify\CsvImport::parse("\xEF\xBB\xBF".$csv))===1,'Admite UTF-8 BOM');
 spCheck(!str_contains(json_encode($db->query('SELECT * FROM fm_service_commands')->fetch_all(MYSQLI_ASSOC)),'private-csv-password'),'Comandos no almacenan contraseñas en claro');
 // Credenciales editables en asignación administrativa, traslado y rehabilitación.
 $credentialMain=$main;$credentialMain['email']='credential-main@example.test';$credentialMain['accounts']=[['email'=>'credential-secondary@example.test','password'=>'original-secret','profiles'=>[['name'=>'Perfil 1']]]];$op($admin,'save',$credentialMain);
 $r=$repo->listing($admin,['q'=>'credential-secondary@example.test'])['rows'][0];
 $assigned=$op($admin,'obtain',['service_id'=>$serviceId,'profile_id'=>$r['profile_id'],'advisor_id'=>$advisor,'phone'=>'+12025550888','client_name'=>'Cliente Credenciales','start_date'=>date('Y-m-d'),'password'=>'assigned-secret','profile_name'=>'Asignado','account_revision'=>$r['account_revision']]);
 spCheck($repo->assignedDetails($advisor,$assigned['assignment_id'])['password']==='assigned-secret','Asigna cuenta elegida y cambia credenciales');
 $db->execute_query("UPDATE fm_user_permissions SET allowed=1 WHERE user_id=? AND permission_code='services.spotify'",[$other]);
 $r=$repo->listing($admin,['q'=>'credential-secondary@example.test'])['rows'][0];
 $op($admin,'transfer',['assignment_id'=>$r['assignment_id'],'revision'=>$r['revision'],'advisor_id'=>$other,'password'=>'transfer-secret','profile_name'=>'Trasladado','account_revision'=>$r['account_revision']]);
 spCheck($repo->assignedDetails($other,$assigned['assignment_id'])['profile']==='Trasladado','Traslado actualiza perfil y conserva asignación');
 $r=$repo->listing($admin,['q'=>'credential-secondary@example.test'])['rows'][0];$op($admin,'fall',['assignment_id'=>$r['assignment_id'],'revision'=>$r['revision'],'account_revision'=>$r['account_revision']]);
 $r=$repo->listing($admin,['q'=>'credential-secondary@example.test'])['rows'][0];$op($admin,'rehabilitate',['account_id'=>$r['account_id'],'account_revision'=>$r['account_revision'],'profile_id'=>$r['profile_id'],'password'=>'restored-secret','profile_name'=>'Restaurado']);
 $r=$repo->listing($admin,['q'=>'credential-secondary@example.test'])['rows'][0];spCheck($r['password']==='restored-secret'&&$r['profile']==='Restaurado'&&!$r['assignment_id'],'Restauración modifica credenciales sin restaurar asignación');
 spCheck($repo->listing($advisor,['expiry'=>'current'])['total']>=0,'Filtro Al día aceptado');
 $edit=$repo->main($admin,(int)$r['main_id']);$removedAccount=$edit['accounts'][0];$edit['accounts']=[];$edit['removed_accounts']=[['id'=>$removedAccount['id'],'revision'=>$removedAccount['revision']]];
 $op($admin,'save',$edit);spCheck($repo->listing($admin,['q'=>'credential-secondary@example.test'])['total']===0,'Baja libre desaparece de ventas');
 spCheck($repo->main($admin,(int)$r['main_id'])['accounts']===[],'Principal puede quedar sin secundarias');
 spCheck((int)$db->execute_query('SELECT COUNT(*) n FROM fm_service_assignments WHERE id=?',[$assigned['assignment_id']])->fetch_assoc()['n']===1,'Baja conserva historial');
 $owned=$repo->listing($advisor,[])['rows'][0];$edit=$repo->main($admin,(int)$db->execute_query('SELECT main_id FROM fm_service_accounts WHERE id=?',[$owned['account_id']])->fetch_assoc()['main_id']);
 $target=array_values(array_filter($edit['accounts'],fn($a)=>(int)$a['id']===(int)$owned['account_id']))[0];$edit['accounts']=array_values(array_filter($edit['accounts'],fn($a)=>(int)$a['id']!==(int)$owned['account_id']));$edit['removed_accounts']=[['id'=>$target['id'],'revision'=>$target['revision']]];
 spReject(fn()=>$op($admin,'save',$edit),409,'Servidor impide quitar cuenta asignada');
 $personal=$repo->dashboard($advisor);$global=$repo->dashboard($admin);
 $own=(int)$db->execute_query('SELECT COUNT(DISTINCT p.account_id) n FROM fm_service_profiles p JOIN fm_service_assignments a ON a.id=p.current_assignment_id WHERE a.advisor_id=?',[$advisor])->fetch_assoc()['n'];
 spCheck($personal['accounts']['assigned']===$own&&$personal['accounts']['free']===0,'Dashboard operativo muestra solo cuentas propias, sin bolsa libre');
 spCheck(!$personal['admin']&&$global['admin'],'Solo administrador recibe indicador administrativo');
 spCheck($personal['payments']===null,'Pagos no disponibles para asesor');
 $owned=$repo->listing($advisor,[])['rows'][0];$op($advisor,'fall',['assignment_id'=>$owned['assignment_id'],'revision'=>$owned['revision'],'account_revision'=>$owned['account_revision']]);
 spCheck($repo->dashboard($advisor)['accounts']['fallen']===1,'Asesor conserva conteo de cuenta propia caída');spCheck($repo->dashboard($other)['accounts']['fallen']===0,'No expone caída ajena');
 spCheck($repo->metadata($admin)['fallen']>0&&$repo->metadata($advisor)['fallen']===0,'Aviso de caídas exclusivo de administrador');
 $db->execute_query("UPDATE fm_user_permissions SET allowed=0 WHERE user_id=? AND permission_code='services.spotify'",[$other]);spReject(fn()=>$repo->dashboard($other),403,'Resumen Spotify requiere permiso');
 $fallen=$repo->listing($admin,['q'=>$owned['email'],'state'=>'fallen'])['rows'][0];
 $op($admin,'rehabilitate',['account_id'=>$fallen['account_id'],'account_revision'=>$fallen['account_revision']]);
 $ready=$repo->listing($admin,['q'=>$owned['email']])['rows'][0];
 $op($admin,'fall',['account_id'=>$ready['account_id'],'account_revision'=>$ready['account_revision']]);
 $latest=$repo->listing($admin,['q'=>$owned['email'],'state'=>'fallen'])['rows'][0];
 spCheck((int)$latest['fallen_reporter_id']===$admin,'Nueva caída muestra a quien reportó esta vez, no al antiguo asesor');
 spCheck($repo->dashboard($advisor)['payments']===null,'Asesor no recibe datos de pagos en dashboard');
 spCheck(is_array($repo->dashboard($admin)['payments']),'Administrador recibe pagos en dashboard');
 echo "$count comprobaciones de Spotify correctas, en base temporal y sin servicios externos.\n";
}finally{$db->query("DROP DATABASE IF EXISTS `$name`");$db->close();}
