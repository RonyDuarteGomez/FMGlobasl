<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/bootstrap/app.php';
use FMGlobal\Repositories\{UserRepository,ScheduleRepository};
use FMGlobal\Services\Users\{UserService,ScheduleService};
$config=require FM_ROOT.'/config/database.php';
if(!in_array($config['host'],['localhost','127.0.0.1'],true)||!str_ends_with($config['database'],'_local'))throw new RuntimeException('Estas pruebas requieren una base local con sufijo _local.');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$admin=new mysqli($config['host'],$config['user'],$config['password']);
$testDb='fmglobal_test_'.bin2hex(random_bytes(6));$source=$config['database'];
if(!preg_match('/^[a-zA-Z0-9_]+$/D',$source))throw new RuntimeException('Nombre de base no válido.');
$count=0;$server=null;$clients=[];$db=null;$serverLog=tempnam(sys_get_temp_dir(),'fmglobal-server-');
function verify(bool $ok,string $label):void{global $count;if(!$ok)throw new RuntimeException($label);$count++;}
function http($client,string $route,string $method='GET',array $data=[]):array{
 global $base;
 curl_setopt_array($client,[CURLOPT_URL=>$base.$route,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>5,CURLOPT_POSTFIELDS=>$method==='POST'?http_build_query($data):null]);
 curl_setopt($client,CURLOPT_CUSTOMREQUEST,$method);
 $raw=curl_exec($client);if($raw===false)throw new RuntimeException('HTTP local no disponible: '.curl_error($client));
 $size=curl_getinfo($client,CURLINFO_HEADER_SIZE);
 return ['status'=>curl_getinfo($client,CURLINFO_RESPONSE_CODE),'headers'=>substr($raw,0,$size),'body'=>substr($raw,$size)];
}
function token(string $body):string{if(!preg_match('/name="_csrf" value="([a-f0-9]+)"/',$body,$m))throw new RuntimeException('Falta CSRF en formulario');return $m[1];}
function browser(){global $clients;$c=curl_init();curl_setopt($c,CURLOPT_COOKIEFILE,'');$clients[]=$c;return $c;}
function login($client,string $name):string{
 $page=http($client,'login.php');$before=curl_getinfo($client,CURLINFO_COOKIELIST);
 $r=http($client,'login.php','POST',['usuario'=>$name,'clave'=>'PruebaTemporal123!','_csrf'=>token($page['body'])]);
 verify($r['status']===302,'Login: '.$name);verify($before!==curl_getinfo($client,CURLINFO_COOKIELIST),'Regenerar sesión: '.$name);
 $home=http($client,'home.php');verify($home['status']===200,'Panel: '.$name);
 preg_match('/name="csrf-token" content="([a-f0-9]+)"/',$home['body'],$m);return $m[1];
}
try{
 $admin->query("CREATE DATABASE `$testDb` CHARACTER SET utf8mb4");
 foreach(['usuarios','personal','activacion','rol','uso_servicio','gmail_tokens'] as $table)$admin->query("CREATE TABLE `$testDb`.`$table` LIKE `$source`.`$table`");
 $db=new mysqli($config['host'],$config['user'],$config['password'],$testDb);$db->set_charset('utf8mb4');
 $db->query("INSERT INTO rol(rol_id,rol_nombre) VALUES(1,'Administrador'),(2,'Asesor'),(3,'Soporte')");
 \FMGlobal\Repositories\PermissionMigration::apply($db);
 \FMGlobal\Repositories\LinkAccountMigration::apply($db);
 \FMGlobal\Repositories\SpotifyMigration::apply($db);
 \FMGlobal\Repositories\ExternalLinkMigration::apply($db);
 \FMGlobal\Repositories\GmailMigration::apply($db);
 \FMGlobal\Repositories\GmailMigration::apply($db);
 $gmail=new \FMGlobal\Repositories\GmailTokenRepository($db);
 $gmail->save('fixture@example.test','synthetic-token');
 $gmailId=(int)$db->query("SELECT id FROM gmail_tokens WHERE correo='fixture@example.test'")->fetch_assoc()['id'];
 verify($gmail->find('fixture@example.test')==='synthetic-token','Gmail autorizado disponible');
 $gmail->deactivate($gmailId);
 verify($gmail->find('fixture@example.test')===null && $gmail->listAuthorized()->num_rows===0,'Baja Gmail excluye consultas y listado');
 verify($db->query("SELECT refresh_token FROM gmail_tokens WHERE id=$gmailId")->fetch_assoc()['refresh_token']==='synthetic-token','Baja logica conserva registro y token');
 $gmail->save('fixture@example.test','new-synthetic-token');
 verify($gmail->find('fixture@example.test')==='new-synthetic-token' && $gmail->listAuthorized()->num_rows===1,'Reautorizar reactiva sin duplicar');
 $gmail->invalidate('fixture@example.test','synthetic-token');
 verify($gmail->find('fixture@example.test')==='new-synthetic-token','Un fallo antiguo no invalida el token renovado');
 $gmail->invalidate('fixture@example.test','new-synthetic-token');
 verify($gmail->find('fixture@example.test')===null,'Token invalido no se usa en consultas');
 verify((int)$db->query("SELECT token_valido FROM gmail_tokens WHERE id=$gmailId")->fetch_assoc()['token_valido']===0,'Invalidacion persiste estado');
 $gmail->save('fixture@example.test','renewed-token');
 verify($gmail->find('fixture@example.test')==='renewed-token','Reautorizar restaura token valido');
 $gmail->save('empty@example.test','');
 verify($gmail->find('empty@example.test')===null,'Token vacio no permite consultar');
 verify(!array_key_exists('refresh_token',$gmail->listAuthorized()->fetch_assoc()),'Listado no expone tokens');
 $users=new UserRepository($db);$schedules=new ScheduleRepository($db);$service=new UserService($users,$schedules);
 $seed=fn($name,$role)=>['usuario'=>$name,'nombre'=>'Prueba','apellido_paterno'=>'Prueba','rol'=>(string)$role,'clave'=>'PruebaTemporal123!'];
 $adminId=$service->save($seed('test_admin',1),'test_admin',0);
 $advisorId=$service->save($seed('test_asesor',2),'test_admin',$adminId);
 $supportId=$service->save($seed('test_soporte',3),'test_admin',$adminId);
 verify(count($schedules->forUser($advisorId))===7,'Alta crea siete horarios');
 $id=$service->save($seed('test_edit',2)+['celular'=>'999111222','cargo'=>'Guardado','hora_inicio_1'=>'00','hora_fin_1'=>'00'],'test_admin',$adminId);
 verify($users->forLogin('test_edit')!==null,'Alta crea usuario');
 verify($schedules->forUser($id)[0]['hora_inicio']==='09:00:00','Alta conserva horario inicial aunque el formulario oculte campos');
 $old=$users->forLogin('test_edit');
 $service->save(['usuario_id'=>$id,'nombre'=>'Editado','rol'=>'2','clave'=>''],'test_admin',$adminId);
 verify($schedules->forUser($id)[0]['hora_inicio']==='09:00:00','Editar usuario conserva horarios existentes');
 verify($users->details($id)['nombre']==='Editado','Edición modifica datos personales');
 try{$service->save($seed('test_edit',2),'test_admin',$adminId);throw new RuntimeException('No rechazó duplicado');}catch(\FMGlobal\Http\HttpException $e){verify($e->status===409,'Usuario duplicado rechazado');}
 $csvImporter=new \FMGlobal\Services\Users\CsvImport($db);
 $csvHeader=implode(',',\FMGlobal\Services\Users\CsvImport::COLUMNS)."\n";
 $csvResult=$csvImporter->import($adminId,'test_admin',$csvHeader."CSV Persona,Apellido,,csv_persona@example.test,+51911111111,csv_persona,Valid123!,Asesor\nCSV Duplicado,Apellido,,,,csv_persona,Valid123!,Asesor\nCSV Espacio,Apellido,,,999 111,csv_espacio,Valid123!,Asesor\nCSV Perfil,Apellido,,,,csv_perfil,Valid123!,NoExiste\n");
 verify($csvResult['imported']===1&&count($csvResult['errors'])===3,'CSV de usuarios importa válidos y registra rechazos');
 verify($users->forLogin('csv_persona')!==null&&password_verify('Valid123!',$users->forLogin('csv_persona')['password_hash']),'CSV guarda contraseña con hash');
 verify(!str_contains(json_encode($csvResult),'Valid123!'),'CSV de rechazos no expone contraseñas');
 foreach(['clave','telefono'] as $field){foreach([' conespacio','con espacio',"con\t_tab","con\u{00A0}nbsp"] as $invalid){
  foreach([false,true] as $edit){$input=array_replace($seed('space_rejected',2),[$field=>$invalid]);if($edit)$input['usuario_id']=$id;
   try{$service->save($input,'test_admin',$adminId);throw new RuntimeException('Espacio admitido');}catch(\FMGlobal\Http\HttpException $e){verify($e->status===422,'Sin espacios en '.$field.($edit?' edición':' alta'));}
  }
 }}
 $db->execute_query('UPDATE usuarios SET estado=0 WHERE id=?',[$id]);
 verify(!isset(\FMGlobal\Repositories\UserNames::selectable($db)[$id]),'Selectores excluyen inactivos');
 verify(isset(\FMGlobal\Repositories\UserNames::all($db)[$id]),'Historial conserva nombres de inactivos');
 $db->execute_query('UPDATE usuarios SET estado=1 WHERE id=?',[$id]);
 $db->query("CREATE TRIGGER fail_schedule BEFORE INSERT ON activacion FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Prueba de rollback'");
 try{$service->save($seed('test_rollback',2),'test_admin',$adminId);throw new RuntimeException('No falló el trigger');}catch(mysqli_sql_exception $e){}
 $db->query('DROP TRIGGER fail_schedule');verify($users->forLogin('test_rollback')===null,'Rollback evita usuario parcial');
 $db->query("INSERT INTO activacion(hora_inicio,hora_fin,dia,usuario_registro,fecha_registro,usuario_id) VALUES('09:00:00','19:00:00','LUNES','original',NOW(),0)");$publicId=$db->insert_id;
 \FMGlobal\Repositories\PublicScheduleRepository::install($db);$weeklyRepo=new \FMGlobal\Repositories\PublicScheduleRepository($db);$initial=$weeklyRepo->load();
 verify($initial['week'][1]['slots']===[['start'=>'00:00','end'=>'09:00'],['start'=>'19:00','end'=>'24:00']],'Migración conserva horarios activos equivalentes');
 \FMGlobal\Repositories\PublicScheduleRepository::install($db);verify($weeklyRepo->load()===$initial,'Migración repetida conserva programación');
 $socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$errstr);$address=stream_socket_get_name($socket,false);fclose($socket);$base='http://'.$address.'/';
 $env=getenv();$env['FMGLOBAL_DB_HOST']=$config['host'];$env['FMGLOBAL_DB_USER']=$config['user'];$env['FMGLOBAL_DB_PASSWORD']=$config['password'];$env['FMGLOBAL_DB_NAME']=$testDb;
 $server=proc_open([PHP_BINARY,'-S',$address,'-t',FM_ROOT.'/public',FM_ROOT.'/tests/helpers/http-router.php'],[0=>['pipe','r'],1=>['file',$serverLog,'a'],2=>['file',$serverLog,'a']],$pipes,FM_ROOT,$env);
 if(!is_resource($server))throw new RuntimeException('No se pudo iniciar PHP local');fclose($pipes[0]);$probe=browser();$ready=false;
 for($i=0;$i<40;$i++){try{$r=http($probe,'login.php');$ready=true;break;}catch(RuntimeException $e){usleep(100000);}}
 if(!$ready)throw new RuntimeException('El servidor temporal no inició');
 verify(str_contains($r['headers'],'HttpOnly')&&str_contains($r['headers'],'SameSite=Lax'),'Cookies HttpOnly y SameSite');
 verify(http($probe,'usuario/usuarios.php')['status']===401,'Anónimo sin acceso a usuarios');
 verify(http($probe,'activacion/actualizar_activacion.php','POST',['id'=>$publicId])['status']===401,'Anónimo no modifica horarios');
 verify(http($probe,'usuario/usuario_save.php')['status']===405,'Mutación rechaza GET');
 verify(http($probe,'login.php','POST',['usuario'=>'test_admin','clave'=>'PruebaTemporal123!'])['status']===419,'Login requiere CSRF');
 $loginPage=http($probe,'login.php');
 verify(str_contains($loginPage['headers'],'X-Frame-Options: DENY') && str_contains($loginPage['headers'],"frame-ancestors 'none'"),'Login impide incrustacion en iframe');
 $loginToken=token($loginPage['body']);
 $unknown=http($probe,'login.php','POST',['usuario'=>'nonexistent_fixture','clave'=>'incorrecta','_csrf'=>$loginToken]);
 $wrong=http($probe,'login.php','POST',['usuario'=>'test_admin','clave'=>'incorrecta','_csrf'=>$loginToken]);
 verify($unknown['status']===$wrong['status'] && str_contains($unknown['body'],'Usuario o contraseña incorrectos') && str_contains($wrong['body'],'Usuario o contraseña incorrectos'),'Login no revela existencia de cuentas');
 $adminBrowser=browser();$csrf=login($adminBrowser,'test_admin');
 foreach(['inicio.php','usuario/usuarios.php','usuario/usuario_list.php','activacion/activacion.php','mantenimiento_gmail.php','soporte/reportes.php','soporte/asesor.php','soporte/soporte.php','soporte/link.php'] as $route)verify(http($adminBrowser,$route)['status']===200,'Admin: '.$route);
 verify(http($adminBrowser,'usuario/usuario_save.php','POST',$seed('csrf_rejected',2))['status']===419,'Guardar requiere CSRF');
 verify($users->forLogin('csrf_rejected')===null,'CSRF no escribe datos');
 verify(http($adminBrowser,'usuario/usuarios.php','POST',['action'=>'import'])['status']===419,'CSV usuarios exige CSRF');
 $template=http($adminBrowser,'usuario/usuarios.php?action=template');verify($template['status']===200&&str_contains($template['body'],'nombre,apellido_paterno,apellido_materno,correo,telefono,usuario,clave,perfil'),'Modelo CSV específico de usuarios');
 verify(http($adminBrowser,'usuario/usuarios.php','POST',['action'=>'import','_csrf'=>$csrf])['status']===422,'Importación usuarios valida archivo adjunto');

 foreach(['nombre','apellido_paterno','rol','usuario','clave'] as $required) {
   $invalid=array_replace($seed('invalid_required',2),[$required=>'','_csrf'=>$csrf]);
   verify(http($adminBrowser,'usuario/usuario_save.php','POST',$invalid)['status']===422,'Campo obligatorio: '.$required);
 }
 foreach(["dos palabras"," usuario","usuario ","dos\tpalabras","dos\npalabras","dos\u{00A0}palabras"] as $invalidName) {
   verify(http($adminBrowser,'usuario/usuario_save.php','POST',array_replace($seed('valid_name',2),['usuario'=>$invalidName,'_csrf'=>$csrf]))['status']===422,'Usuario no admite espacios');
 }
 verify(http($adminBrowser,'session.php')['status']===405,'Renovacion solo admite POST');
 verify(http($adminBrowser,'session.php','POST')['status']===419,'Renovacion exige CSRF');
 $renew=http($adminBrowser,'session.php','POST',['_csrf'=>$csrf]);
 verify($renew['status']===200 && json_decode($renew['body'],true)['remaining']===1800,'Renovacion autenticada concede 30 minutos');
 verify(str_contains($renew['headers'],'X-Session-Remaining: 1800'),'Respuesta informa plazo al navegador');
 $anonymousSession=browser();verify(http($anonymousSession,'session.php','POST',['_csrf'=>'invalid'])['status']===401,'Renovacion no crea acceso anonimo');
 $input=$seed('http_created',2)+['_csrf'=>$csrf];$r=http($adminBrowser,'usuario/usuario_save.php','POST',$input);verify($r['status']===200&&str_contains($r['body'],'correctamente'),'Alta por HTTP');
 verify(http($adminBrowser,'usuario/usuario_save.php','POST',$input)['status']===409,'Duplicado controlado');
 $created=$users->forLogin('http_created');$createdDetails=$users->details((int)$created['id']);
 $get=http($adminBrowser,'usuario/usuario_get.php?usuario_id='.$created['id'].'&personal_id='.$createdDetails['personal_id']);verify(json_decode($get['body'],true)['success']===true,'Contrato de edición JSON');
 verify(http($adminBrowser,'usuario/usuario_save.php','POST',['_csrf'=>$csrf,'usuario_id'=>$created['id'],'nombre'=>'Editado HTTP','rol'=>'2','clave'=>'','hora_inicio_1'=>'22','hora_fin_1'=>'10'])['status']===422,'Horario inválido rechazado');
 verify($users->details((int)$created['id'])['nombre']==='Prueba','Validación ocurre antes de guardar');
 $toggle=http($adminBrowser,'usuario/usuario_toggle.php','POST',['_csrf'=>$csrf,'usuario_id'=>$created['id']]);verify($toggle['status']===200&&(int)$users->identity((int)$created['id'])['estado']===0,'Desactivar por HTTP');
 verify(http($adminBrowser,'usuario/usuario_toggle.php','POST',['_csrf'=>$csrf,'usuario_id'=>$adminId])['status']===409,'Evitar bloqueo propio');
 $week=array_fill(1,7,['mode'=>'all','slots'=>[]]);
 $payload=['_csrf'=>$csrf,'revision'=>'0','schedule'=>json_encode($week)];
 verify(http($adminBrowser,'activacion/actualizar_activacion.php','POST',array_diff_key($payload,['_csrf'=>true]))['status']===419,'Programación exige CSRF');
 $update=http($adminBrowser,'activacion/actualizar_activacion.php','POST',$payload);
 verify($update['status']===200 && json_decode($update['body'],true)['revision']===1,'Guardado semanal devuelve nueva versión');
 verify($db->query('SELECT updated_by FROM fm_public_schedule WHERE id=1')->fetch_assoc()['updated_by']==='test_admin','Programación registra actor real');
 verify(http($adminBrowser,'activacion/actualizar_activacion.php','POST',$payload)['status']===409,'Versión obsoleta no pisa cambios ajenos');
 $invalid=$week;$invalid[2]=['mode'=>'hours','slots'=>[['start'=>'09:00','end'=>'12:00'],['start'=>'11:00','end'=>'13:00']]];
 verify(http($adminBrowser,'activacion/actualizar_activacion.php','POST',['_csrf'=>$csrf,'revision'=>1,'schedule'=>json_encode($invalid)])['status']===422 && $weeklyRepo->load()['revision']===1,'Solapamientos rechazan la semana completa sin cambios parciales');
 $off=array_fill(1,7,['mode'=>'off','slots'=>[]]);
 verify(http($adminBrowser,'activacion/actualizar_activacion.php','POST',['_csrf'=>$csrf,'revision'=>1,'schedule'=>json_encode($off)])['status']===200,'Deshabilitar toda la semana');
 verify(str_contains(http($probe,'validacion.php')['body'],'Servicio deshabilitado temporalmente'),'Validación pública aplica la programación');
 verify(str_contains(http($adminBrowser,'validacion.php')['body'],'Servicio deshabilitado temporalmente'),'La sesión no elude la programación pública');
 verify(str_contains(http($adminBrowser,'dashboard.php')['body'],'>Inactivo</strong>'),'Dashboard y Validación comparten estado');
 verify(http($adminBrowser,'activacion/actualizar_activacion.php','POST',['_csrf'=>$csrf,'revision'=>2,'schedule'=>json_encode($week)])['status']===200,'Habilitar semana completa');
 verify(!str_contains(http($probe,'validacion.php')['body'],'Servicio deshabilitado temporalmente'),'Validación vuelve a mostrar servicios al habilitar');
 verify($db->query('SELECT hora_inicio FROM activacion WHERE id='.$publicId)->fetch_assoc()['hora_inicio']==='09:00:00','Programación nueva conserva datos legados');
 $advisor=browser();$advisorCsrf=login($advisor,'test_asesor');
 verify(http($advisor,'activacion/actualizar_activacion.php','POST',['_csrf'=>$advisorCsrf,'revision'=>3,'schedule'=>json_encode($week)])['status']===403,'Asesor no cambia la programación pública');
 verify(http($advisor,'soporte/asesor.php')['status']===200,'Asesor accede a su módulo');
 foreach(['usuario/usuarios.php','activacion/activacion.php','soporte/reportes.php','soporte/soporte.php','mantenimiento_gmail.php'] as $route)verify(http($advisor,$route)['status']===403,'Asesor bloqueado: '.$route);
 verify(http($advisor,'usuario/usuario_save.php','POST',$seed('forbidden',2)+['_csrf'=>$advisorCsrf])['status']===403,'Asesor no puede crear usuario');
 verify(!str_contains(http($advisor,'home.php')['body'],'"labels":["'),'Asesor no recibe estadísticas');
 $support=browser();$supportCsrf=login($support,'test_soporte');
 verify(http($support,'soporte/soporte.php')['status']===200,'Soporte accede a su módulo');
 verify(http($support,'soporte/asesor.php')['status']===403,'Soporte sin permiso de asesor');
 verify(http($support,'soporte/procesar_netflix1.php','POST',['_csrf'=>$supportCsrf,'correo'=>'a@example.com'])['status']===403,'Permiso antes de cualquier consulta externa');
 // Dynamic permissions and personal dashboard isolation.
 $catalog=array_keys(\FMGlobal\Security\PermissionCatalog::ITEMS);
 $permissionRepo=new \FMGlobal\Repositories\PermissionRepository($db);
 $permissionWrites=0;
 $updatePermissions=function($type,$id,$overrides,$revision=null)use($catalog,$permissionRepo,$adminBrowser,$csrf,&$permissionWrites){
   $values=array_fill_keys($catalog,$type==='user'?'inherit':'deny');
   $response=http($adminBrowser,'permisos/save.php','POST',['_csrf'=>$csrf,'type'=>$type,'id'=>$id,'revision'=>$revision??$permissionRepo->revision(),'permissions'=>array_replace($values,$overrides)]); if ($response['status']===200) $permissionWrites++; return $response;
 };
 foreach([['test_asesor','advisor.only@example.com',3],['test_soporte','support.only@example.com',6],['test_admin','admin.secret@example.com',3],[null,'public.secret@example.com',1]] as [$who,$email,$operation]) $db->execute_query('INSERT INTO uso_servicio(correo,num_urls,fecha,usuario,streaming) VALUES(?,1,NOW(),?,?)',[$email,$who,$operation]);
 \FMGlobal\Repositories\UsageUserMigration::apply($db);
 verify(http($probe,'dashboard.php')['status']===401,'Dashboard exige sesión');
 $dash=http($advisor,'dashboard.php')['body'];
 $advisorDashboard=(new \FMGlobal\Services\Reports\PersonalDashboard(new \FMGlobal\Repositories\ActivityRepository($db)))->build($permissionRepo->effective($advisorId),$advisorId);
 verify((int)$advisorDashboard['summary']['total']===1 && $advisorDashboard['cards'][0]['mix']===['netflix'=>1,'disney'=>0] && !str_contains($dash,'@example.com'),'Dashboard agregado de asesor solo cuenta actividad propia autorizada');
 $dash=http($support,'dashboard.php')['body'];verify(str_contains($dash,'Consultas soporte') && !str_contains($dash,'@example.com') && !str_contains($dash,'Consultas asesores'),'Dashboard soporte sin tablas ni actividad de asesor');
 $activity=new \FMGlobal\Repositories\ActivityRepository($db);
 verify($activity->platforms([1,2],null)===['netflix'=>1,'disney'=>0],'Distribución pública excluye asesor');
 $db->execute_query("INSERT INTO uso_servicio(correo,num_urls,fecha,usuario,usuario_id,streaming) VALUES('',1,NOW(),'test_asesor',?,4),('',1,DATE_SUB(NOW(),INTERVAL 31 DAY),'test_asesor',?,4)",[$advisorId,$advisorId]);
 verify($activity->platforms([3,4,5],$advisorId)===['netflix'=>1,'disney'=>1],'Mezcla distingue plataformas y excluye registros antiguos');
 verify($activity->advisorBreakdown($advisorId)===['netflix_access'=>1,'netflix_login'=>0,'disney'=>1],'Desglose asesor separa acceso/inicio, filtra usuario y período');
 $db->execute_query("INSERT INTO uso_servicio(correo,num_urls,fecha,usuario,usuario_id,streaming) VALUES('dashboard-split@example.test',1,NOW(),'test_asesor',?,5)",[$advisorId]);
 verify($activity->advisorBreakdown($advisorId)===['netflix_access'=>1,'netflix_login'=>1,'disney'=>1],'Inicio Netflix no se suma al código de acceso');
 $splitDashboard=http($advisor,'dashboard.php')['body'];
 verify(str_contains($splitDashboard,'Netflix · Código de acceso') && str_contains($splitDashboard,'Netflix · Código de inicio') && !str_contains($splitDashboard,'VISTA ALTERNATIVA'),'Inicio usa dashboard nuevo con tres tipos de consulta');
 $db->query("DELETE FROM uso_servicio WHERE correo='dashboard-split@example.test'");
 verify(http($probe,'soporte/link.php','POST',['event'=>'generated'])['status']===401,'Registro de link exige sesión');
 verify(http($advisor,'mantenimiento_gmail.php','POST',['_csrf'=>$advisorCsrf,'id'=>$gmailId])['status']===403,'Baja Gmail exige permiso');
 verify(http($adminBrowser,'mantenimiento_gmail.php','POST',['id'=>$gmailId])['status']===419,'Baja Gmail exige CSRF');
 verify($gmail->find('fixture@example.test')!==null,'Peticiones rechazadas no desactivan Gmail');
 verify(http($adminBrowser,'mantenimiento_gmail.php','POST',['_csrf'=>$csrf,'id'=>$gmailId])['status']===303,'Baja Gmail autorizada redirige al modulo');
 verify($gmail->find('fixture@example.test')===null,'Baja HTTP excluye Gmail del proveedor');
 verify(http($advisor,'soporte/link.php','POST',['_csrf'=>$advisorCsrf,'event'=>'generated'])['status']===403,'Registro de link exige permiso');
 verify(http($adminBrowser,'soporte/link.php','POST',['event'=>'generated'])['status']===419,'Registro de link exige CSRF');
 verify(http($adminBrowser,'soporte/link.php','POST',['_csrf'=>$csrf,'event'=>'other'])['status']===422,'Registro rechaza eventos desconocidos');
 verify(http($adminBrowser,'soporte/link.php','POST',['_csrf'=>$csrf,'event'=>'generated'])['status']===422,'No acepta generaciones declaradas por el navegador');
 $linkRepo=new \FMGlobal\Repositories\LinkAccountRepository($db,new \FMGlobal\Services\Links\AccountVault());
 $accountId=$linkRepo->save($adminId,['external_id'=>'synthetic-id','secure'=>'synthetic-secure','credentials'=>'link@example.test:synthetic-password']);
 $generation=$linkRepo->beginGeneration($adminId,$accountId);
 $linkRepo->finishGeneration($adminId,$accountId,$generation['token'],true);
 verify(http($adminBrowser,'soporte/link.php?action=list')['status']===200,'Administrador lista cuentas');
 verify(http($adminBrowser,'soporte/link.php?action=template')['status']===200,'Administrador descarga CSV modelo');
 verify((int)$activity->summary([7],$adminId)['total']===1 && (int)$activity->summary([7],$advisorId)['total']===0,'Contador de links respeta usuario');
 $builder=new \FMGlobal\Services\Reports\PersonalDashboard($activity);
 $ownSupport=$builder->build(['services.support'=>true,'activity.own'=>true],$supportId);
 verify(count($ownSupport['cards'])===1 && $ownSupport['cards'][0]['destination']==='soporteMenu' && (int)$ownSupport['cards'][0]['summary']['total']===1,'Solo soporte muestra una tarjeta con actividad propia');
 $ownLinks=$builder->build(['services.links'=>true,'activity.own'=>true],$advisorId);
 verify($ownLinks['cards']===[],'Home no muestra card de links');
 foreach([['services.activation','reports.public','Consultas clientes'],['services.advisor','reports.internal','Consultas asesores'],['services.support','reports.support','Consultas soporte']] as [$module,$report,$title]) {
  foreach([[true,false],[false,true],[true,true],[false,false]] as [$hasModule,$hasReport]) {
   $cards=$builder->build([$module=>$hasModule,$report=>$hasReport],$advisorId)['cards'];
   verify(count($cards)===(int)($hasModule||$hasReport),'Tarjeta OR módulo/reporte: '.$title);
   if($cards){verify($cards[0]['canReport']===$hasReport,'Detalle solo con reporte: '.$title);verify($cards[0]['visible'] && isset($cards[0]['summary']),'Estadísticas sin permiso heredado: '.$title);}
  }
 }
 $limited=$builder->build(['services.advisor'=>true,'activity.all'=>true],$advisorId);
 verify($limited['cards'][0]['summary']['total']==2,'activity.all no amplía datos sin perfil administrador');
 $none=$builder->build([],$advisorId);
 verify($none['cards']===[] && !$none['showActivation'] && $none['activation']===$ownSupport['activation'],'Estado de activación común incluso sin servicios autorizados');
 verify(!str_contains(http($advisor,'dashboard.php')['body'],'activation-indicator') && str_contains(http($advisor,'dashboard.php')['body'],'proposal-activity-grid'),'Asesor recibe estado público y tarjetas en la cuadrícula común');
 verify(http($advisor,'activacion/activacion.php')['status']===403,'Ver estado no concede permiso de administrar activación');
 verify(http($advisor,'dashboard.php?report=advisor')['status']===403,'Servicio asesor no concede acceso a su reporte');
 verify(!str_contains(http($advisor,'home.php')['body'],'id="reportesMenu"'),'Menú oculta reporte sin permiso');
 verify(!str_contains(http($advisor,'dashboard.php')['body'],'report=advisor'),'Dashboard oculta enlace sin permiso');
 $db->execute_query("INSERT INTO fm_user_permissions(user_id,permission_code,allowed) VALUES(?,'reports.internal',1)",[$advisorId]);
 $ownReport=http($advisor,'dashboard.php?report=advisor&usuario=test_admin');
 verify($ownReport['status']===200 && str_contains($ownReport['body'],'advisor.only@example.com') && !str_contains($ownReport['body'],'admin.secret@example.com'),'Permiso individual habilita reporte con alcance propio');
 verify(str_contains(http($advisor,'home.php')['body'],'id="reportesMenu"'),'Menú refleja permiso concedido');
 $db->execute_query("UPDATE fm_user_permissions SET allowed=0 WHERE user_id=? AND permission_code='reports.internal'",[$advisorId]);
 verify(http($advisor,'dashboard.php?report=advisor')['status']===403,'Denegación individual bloquea reporte inmediatamente');
 $db->execute_query("DELETE FROM fm_user_permissions WHERE user_id=? AND permission_code='reports.internal'",[$advisorId]);
 verify(http($advisor,'soporte/reportes.php')['status']===403,'Detalle propio no concede permiso del reporte interno general');
 verify(http($advisor,'dashboard.php?report=support')['status']===403,'Detalle rechaza servicio ajeno');
 verify(http($probe,'dashboard.php?report=links')['status']===401,'Detalle de links exige sesión');
 verify(http($adminBrowser,'dashboard.php?report=unknown')['status']===422,'Detalle rechaza tipo desconocido');
 $supportReport=http($support,'dashboard.php?report=support');
 verify($supportReport['status']===200 && str_contains($supportReport['body'],'support.only@example.com') && !str_contains($supportReport['body'],'advisor.only@example.com'),'Detalle de soporte incluye únicamente su servicio y usuario');
 $linkReport=http($adminBrowser,'dashboard.php?report=links');
 verify($linkReport['status']===200 && str_contains($linkReport['body'],'Prueba Prueba') && str_contains($linkReport['body'],'Consultas link') && !str_contains($linkReport['body'],'advisor.only@example.com'),'Reporte de links muestra generación registrada y usuario');
 verify(http($advisor,'dashboard.php?report=links')['status']===403,'Reporte exige permiso propio');
 $db->execute_query("INSERT INTO fm_user_permissions(user_id,permission_code,allowed) VALUES(?,'reports.links',1)",[$advisorId]);
 $advisorLinks=http($advisor,'dashboard.php?report=links');
 verify($advisorLinks['status']===200 && !str_contains($advisorLinks['body'],'link@example.test'),'Permiso individual permite reporte solo propio');
 $db->execute_query("DELETE FROM fm_user_permissions WHERE user_id=? AND permission_code='reports.links'",[$advisorId]);
 $next=http($adminBrowser,'dashboard.php?report=links&days=7&page=2')['body'];
 verify(str_contains($next,'name="report" value="links"') && str_contains($next,'value="7" selected'),'Filtro y paginación conservan tipo de reporte');
 verify(!str_contains(http($advisor,'dashboard.php')['body'],'data-load-url="dashboard.php?report=advisor"'),'Tarjeta sin permiso no abre reporte detallado');
 verify(http($advisor,'permisos/index.php')['status']===403,'Asesor no gestiona permisos');
 verify(http($adminBrowser,'permisos/save.php','POST',[])['status']===419,'Permisos exigen CSRF');
 verify($updatePermissions('user',$advisorId,['services.support'=>'allow','reports.internal'=>'allow'])['status']===200,'Excepción concede servicio y reporte');
 verify(http($advisor,'soporte/soporte.php')['status']===200,'Permiso individual repercute en servidor');
 verify(str_contains(http($advisor,'home.php')['body'],'id="soporteMenu"'),'Permiso individual repercute en menú');
 $dash=http($advisor,'dashboard.php')['body'];verify(str_contains($dash,'Consultas soporte')&&!str_contains($dash,'support.only@example.com'),'Servicio adicional no amplía actividad ajena');
 $report=http($advisor,'soporte/reportes.php?usuario=test_admin&days=0')['body'];verify(str_contains($report,'advisor.only@example.com')&&!str_contains($report,'admin.secret@example.com'),'Reporte ignora suplantación del filtro usuario');
 verify($updatePermissions('user',$advisorId,['services.support'=>'allow','reports.internal'=>'allow','activity.all'=>'allow'])['status']===200,'Conceder alcance global explícito');
 $report=http($advisor,'soporte/reportes.php')['body'];verify(!str_contains($report,'admin.secret@example.com')&&!str_contains($report,'support.only@example.com')&&str_contains($report,'advisor.only@example.com'),'Perfil operativo no puede ampliar alcance mediante activity.all');
 verify($updatePermissions('user',$advisorId,['services.advisor'=>'deny'])['status']===200,'Excepción deniega permiso heredado');
 verify(http($advisor,'soporte/asesor.php')['status']===403,'Denegación en backend');
 verify(!str_contains(http($advisor,'home.php')['body'],'id="asesorMenu"'),'Denegación en menú');
 verify(!str_contains(http($advisor,'dashboard.php')['body'],'advisor.only@example.com'),'Denegación retira datos de dashboard');
 verify($updatePermissions('user',$advisorId,[])['status']===200,'Restablecer herencia');
 verify(http($advisor,'soporte/asesor.php')['status']===200,'Herencia vuelve a aplicar');
 $roleValues=array_fill_keys(\FMGlobal\Security\PermissionCatalog::defaults(2),'allow');$roleValues['services.links']='allow';
 verify($updatePermissions('role',2,$roleValues)['status']===200,'Modificar perfil en base');
 verify(http($advisor,'soporte/link.php')['status']===200,'Cambio de perfil inmediato');
 \FMGlobal\Repositories\PermissionMigration::apply($db);
verify(!empty($permissionRepo->effective($advisorId)['services.links']),'Migración repetida no restaura permisos antiguos');
 $oldRevision=$permissionRepo->revision();verify($updatePermissions('user',$advisorId,['services.links'=>'deny'])['status']===200,'Denegar excepción sobre perfil ampliado');
 verify($updatePermissions('user',$advisorId,[],$oldRevision)['status']===409,'Evitar guardar una edición desactualizada');
 verify(empty($permissionRepo->effective($advisorId)['services.links']),'Denegación individual prevalece');
 verify($updatePermissions('user',$adminId,['permissions.manage'=>'deny'])['status']===409,'No retirar el último gestor de permisos');
 verify(!empty($permissionRepo->effective($adminId)['permissions.manage']),'Rechazo conserva permisos anteriores');
 verify(http($adminBrowser,'usuario/usuario_save.php','POST',['_csrf'=>$csrf,'usuario_id'=>$adminId,'nombre'=>'Prueba','apellido_paterno'=>'Prueba','rol'=>'2','clave'=>''])['status']===409,'Cambiar perfil tampoco puede eliminar último gestor');
 verify((int)$users->identity($adminId)['rol_id']===1,'Rollback conserva perfil del último gestor');
 $invalid=array_fill_keys($catalog,'inherit');$invalid['unknown.permission']='allow';
 verify(http($adminBrowser,'permisos/save.php','POST',['_csrf'=>$csrf,'type'=>'user','id'=>$advisorId,'revision'=>$permissionRepo->revision(),'permissions'=>$invalid])['status']===422,'Rechazar permisos desconocidos');
 verify($updatePermissions('user',$advisorId,['users.manage'=>'allow'])['status']===200,'Delegar mantenimiento de usuarios');
 verify(http($advisor,'usuario/usuario_save.php','POST',['_csrf'=>$advisorCsrf,'usuario_id'=>$adminId,'nombre'=>'Intruso','rol'=>'1','clave'=>'No permitido'])['status']===403,'Mantenimiento no permite tomar cuenta privilegiada');
 verify(http($advisor,'usuario/usuario_save.php','POST',$seed('escalation',1)+['_csrf'=>$advisorCsrf])['status']===403,'Mantenimiento no permite asignarse perfil privilegiado');
 verify($updatePermissions('user',$advisorId,['activity.own'=>'deny','reports.internal'=>'allow'])['status']===200,'Revocar visualización de actividad');
 $authorizedReport=http($advisor,'soporte/reportes.php');
 verify($authorizedReport['status']===200 && str_contains($authorizedReport['body'],'advisor.only@example.com') && !str_contains($authorizedReport['body'],'admin.secret@example.com'),'Reporte autorizado ignora denegación heredada y conserva datos propios');
 verify(http($advisor,'dashboard.php?report=advisor')['status']===200,'Detalle autorizado no exige activity.own');
 verify(str_contains(http($advisor,'home.php')['body'],'id="reportesMenu"'),'Menú autorizado no exige activity.own');
 verify(!str_contains(http($advisor,'dashboard.php')['body'],'advisor.only@example.com'),'Dashboard conserva resumen sin exponer correos');
 verify($db->query('SELECT id FROM fm_permission_audit')->num_rows===$permissionWrites,'Cambios de permisos auditados');
 verify($updatePermissions('user',$advisorId,[])['status']===200,'Restaurar usuario de prueba');
 $db->query('UPDATE usuarios SET estado=0 WHERE id='.$advisorId);
 verify(http($advisor,'soporte/asesor.php')['status']===401,'Desactivación invalida sesión existente');
 foreach(['buscar_correo','netflix_otp','netflix_link','disney_otp'] as $api)verify(http($probe,'api/'.$api.'.php?correo=invalido')['status']===422,'API valida correo: '.$api);
 foreach(['procesar_disney','procesar_netflix1','procesar_netflix2','procesar_soporte'] as $action) verify(http($adminBrowser,'soporte/'.$action.'.php','POST',['_csrf'=>$csrf,'correo'=>'invalido'])['status']===422,'Consulta rechaza correo inválido antes de API: '.$action);
 // El inicio OAuth solo genera una URL, no contacta a Google.
 $oauth=http($adminBrowser,'oauth_gmail.php','POST',['_csrf'=>$csrf,'correo'=>'fixture@example.test']);verify($oauth['status']===302,'Inicio OAuth con CSRF');
 preg_match('/^Location: (.+)$/mi',$oauth['headers'],$location);parse_str(parse_url(trim($location[1]),PHP_URL_QUERY),$oauthQuery);
 verify(strlen($oauthQuery['state']??'')===64,'OAuth adjunta estado aleatorio');
 verify(($oauthQuery['login_hint']??'')==='fixture@example.test','Renovacion sugiere la cuenta solicitada');
 verify(http($adminBrowser,'oauth2callback.php?state='.urlencode($oauthQuery['state']))['status']===422,'State correcto pero code ausente se rechaza sin conectar');
 verify(http($adminBrowser,'oauth2callback.php?state='.urlencode($oauthQuery['state']))['status']===400,'State no se reutiliza por HTTP');
 verify(http($adminBrowser,'oauth2callback.php?state=incorrecto&code=test')['status']===400,'OAuth inválido se rechaza antes de contactar Google');
 verify(http($adminBrowser,'logout.php')['status']===405,'Logout requiere POST');
 $scopeA=$service->save($seed('scope_a',2),'test_admin',$adminId);
 $scopeB=$service->save($seed('scope_b',2),'test_admin',$adminId);
 $advisorFixture=$service->save($seed('advisor_fixture',2),'test_admin',$adminId);
 $supportFixture=$service->save($seed('support_fixture',3),'test_admin',$adminId);
 $db->query("INSERT INTO uso_servicio(correo,num_urls,fecha,usuario,usuario_id,streaming) VALUES('report-filter@example.test',1,NOW(),'scope_a',$scopeA,1),('report-filter@example.test',2,NOW(),'scope_b',$scopeB,2)");
 $activityFilter=new \FMGlobal\Repositories\ActivityRepository($db);
 $db->query("INSERT INTO uso_servicio(correo,num_urls,fecha,usuario,usuario_id,streaming) VALUES('multi-series@example.test',1,NOW(),'ADVISOR_FIXTURE',$advisorFixture,3),('multi-series@example.test',1,NOW(),'ADVISOR_FIXTURE',$advisorFixture,4),('multi-series@example.test',1,NOW(),'ADVISOR_FIXTURE',$advisorFixture,5),('multi-series@example.test',1,NOW(),'SUPPORT_FIXTURE',$supportFixture,6)");
 $advisorSeries=$activityFilter->dailyByService([3,4,5],$advisorFixture,30,'multi-series');
 $today=date('Y-m-d');
 verify($advisorSeries[$today][3]===1 && $advisorSeries[$today][4]===1 && $advisorSeries[$today][5]===1 && $advisorSeries[$today][6]===0,'Grafico asesores separa tres tipos y excluye soporte');
 $supportSeries=$activityFilter->dailyByService([6],$supportFixture,60,'multi-series');
 verify(count($supportSeries)===60 && $supportSeries[$today][6]===1 && $supportSeries[$today][3]===0,'Grafico soporte usa una operacion y periodo seleccionado');
 verify($activityFilter->searchReport([3,4,5],$advisorFixture,30,'Inicio de sesión',1,20)['total']===1,'Busqueda de asesores reconoce el tipo de consulta');
 $filteredReport=$activityFilter->searchReport([1,2],$scopeA,30,'report-filter',1,10);
 verify($filteredReport['total']===1 && $filteredReport['rows'][0]['usuario']==='scope_a','Busqueda reporte respeta alcance propio');
 verify($activityFilter->searchReport([1,2],null,30,'report-filter',1,10)['total']===2,'Busqueda reporte permite alcance global autorizado');
 verify($activityFilter->searchReport([1],null,30,'report-filter',1,10)['total']===1,'Busqueda reporte respeta operaciones autorizadas');
 verify($activityFilter->searchReport([1,2],null,30,"' OR 1=1 --",1,10)['total']===0,'Busqueda reporte usa parametros');
 verify($activityFilter->searchReport([1,2],null,30,'report-filter',999,10)['page']===1,'Paginacion ajusta pagina fuera de rango');
 verify(http($adminBrowser,'inicio.php?size=500')['status']===422,'Reporte rechaza tamanos no permitidos');
 $usageFixture=new \FMGlobal\Repositories\UsageRepository($db);
 $supportUrl='https://fmglobals.com/api/buscar_correo.php';
 $emptyClient=new \FMGlobal\Services\Http\MailApiClient(fn($url)=>['status'=>200,'body'=>json_encode(['ok'=>true,'data'=>[]])]);
 $emptySearch=new \FMGlobal\Services\Mail\SupportConsultation($emptyClient,$usageFixture);
 $emptySearch->search($supportUrl,'empty-support@example.test',$supportFixture);
 $saved=$db->query("SELECT usuario,num_urls,streaming FROM uso_servicio WHERE correo='empty-support@example.test'")->fetch_all(MYSQLI_ASSOC);
 verify(count($saved)===1 && $saved[0]['usuario']==='support_fixture' && (int)$saved[0]['num_urls']===0 && (int)$saved[0]['streaming']===6,'Soporte vacio registra una consulta con usuario y cero resultados');
 $successClient=new \FMGlobal\Services\Http\MailApiClient(fn($url)=>['status'=>200,'body'=>json_encode(['ok'=>true,'data'=>[['date'=>'2026-09-19 10:00:00','from'=>'fixture','subject'=>'test','body'=>'test']]])]);
 (new \FMGlobal\Services\Mail\SupportConsultation($successClient,$usageFixture))->search($supportUrl,'success-support@example.test',$supportFixture);
 verify((int)$db->query("SELECT COUNT(*) AS total FROM uso_servicio WHERE correo='success-support@example.test' AND num_urls=1")->fetch_assoc()['total']===1,'Soporte exitoso se registra una sola vez');
 $failedClient=new \FMGlobal\Services\Http\MailApiClient(fn($url)=>['status'=>503,'body'=>'']);
 try {(new \FMGlobal\Services\Mail\SupportConsultation($failedClient,$usageFixture))->search($supportUrl,'failed-support@example.test',$supportFixture); throw new RuntimeException('Error esperado');} catch(\FMGlobal\Http\HttpException $e){verify($e->status===502,'Error externo no se convierte en consulta vacia');}
 verify((int)$db->query("SELECT COUNT(*) AS total FROM uso_servicio WHERE correo='failed-support@example.test'")->fetch_assoc()['total']===0,'Error externo no registra exito ficticio');
 verify(str_contains(http($adminBrowser,'home.php')['body'],'id="reporteLinks"'),'Menu incluye Consultas link para administrador');
 $supportOriginal=$permissionRepo->settings('user',$supportId);
 $supportValues=array_fill_keys(array_keys(\FMGlobal\Security\PermissionCatalog::ITEMS),'inherit');
 foreach($supportOriginal as $code=>$value)$supportValues[$code]=$value?'allow':'deny';
 $permissionRepo->save('user',$supportId,array_replace($supportValues,['reports.support'=>'deny']),$adminId,$permissionRepo->revision());
 verify(empty($permissionRepo->effective($supportId)['reports.support']),'Se deniega reporte soporte independientemente');
 $permissionRepo->save('user',$supportId,$supportValues,$adminId,$permissionRepo->revision());
 $db->execute_query('UPDATE usuarios SET usuario=? WHERE id=?',['admin',$adminId]);
 verify($permissionRepo->isSuperuser($adminId),'Reconoce cuenta superusuario reservada');
 // Cada endpoint descarga su propio esquema y nombre de archivo.
 foreach(['soporte/link.php'=>['modelo-cuentas-link.csv','ID,secure,correo_contrasena'],'gestion/spotify.php'=>['spotify-modelo.csv',implode(',',\FMGlobal\Services\Spotify\CsvImport::HEADER)],'gestion/clientes.php'=>['clientes-modelo.csv','nombre,celular']] as $path=>[$filename,$header]){
  $csv=http($adminBrowser,$path.'?action=template');verify($csv['status']===200,'Descarga CSV '.$path);verify(str_contains($csv['headers'],$filename),'Nombre CSV '.$path);verify(strtok(ltrim($csv['body'],"\xEF\xBB\xBF"),"\r\n")===$header,'Columnas propias '.$path);
 }
 $db->execute_query('INSERT INTO fm_user_permissions(user_id,permission_code,allowed) VALUES(?,?,0) ON DUPLICATE KEY UPDATE allowed=0',[$adminId,'services.links']);
 verify(empty($permissionRepo->effective($adminId)['services.links']),'Admin respeta permisos denegados');
 verify(http($adminBrowser,'soporte/link.php')['status']===403,'Admin no elude permiso por URL directa');
 $page=http($adminBrowser,'permisos/index.php');
 verify($page['status']===200 && str_contains($page['body'],'id="permissionModeRole" checked'),'Permisos abre por perfil');
 $page=http($adminBrowser,'permisos/index.php?type=user');
 verify($page['status']===200 && !str_contains($page['body'],'<option value="'.$adminId.'"'),'Selector excluye admin');
 verify(http($adminBrowser,'permisos/index.php?type=user&id='.$adminId)['status']===404,'Admin no aparece como destinatario en el selector de permisos');
 $values=array_fill_keys(array_keys(\FMGlobal\Security\PermissionCatalog::ITEMS),'deny');
 verify(http($adminBrowser,'permisos/save.php','POST',['_csrf'=>$csrf,'type'=>'user','id'=>$adminId,'revision'=>$permissionRepo->revision(),'permissions'=>$values])['status']===409,'No se puede quitar al último gestor, también para admin');
 $db->execute_query('UPDATE usuarios SET usuario=? WHERE id=?',['test_admin',$adminId]);
 verify(http($adminBrowser,'logout.php','POST',['_csrf'=>$csrf])['status']===302,'Logout con CSRF');
 verify(http($adminBrowser,'usuario/usuarios.php')['status']===401,'Logout elimina sesión');
 // Spotify: ruta real, permiso independiente, CSRF y minimización de datos.
 $db->execute_query('UPDATE usuarios SET estado=1 WHERE id=?',[$advisorId]);$advisorCsrf=login($advisor,'test_asesor');$csrf=login($adminBrowser,'test_admin');
 verify(http($probe,'gestion/spotify.php?action=metadata')['status']===401,'Spotify exige sesión');
 verify(http($advisor,'gestion/spotify.php')['status']===403,'Spotify inicialmente denegado al asesor');
 verify(http($adminBrowser,'gestion/spotify.php')['status']===200,'Administrador abre Spotify');
 verify(str_contains(http($adminBrowser,'home.php')['body'],'id="Spotify"'),'Spotify integrado en menú');
 verify(http($adminBrowser,'gestion/spotify.php','POST',['action'=>'obtain','payload'=>'{}'])['status']===419,'Spotify exige CSRF en mutaciones');
 $db->execute_query("INSERT INTO fm_user_permissions(user_id,permission_code,allowed) VALUES(?,'services.spotify',1) ON DUPLICATE KEY UPDATE allowed=1",[$advisorId]);
 verify(http($advisor,'gestion/spotify.php')['status']===200,'Excepción de usuario habilita Spotify');
 $spMeta=json_decode(http($adminBrowser,'gestion/spotify.php?action=metadata')['body'],true);$spService=(int)$spMeta['services'][0]['id'];
 $spPayload=['request_key'=>bin2hex(random_bytes(16)),'service_id'=>$spService,'email'=>'main-sp@example.test','payment_email'=>'payment-sp@example.test','next_payment'=>date('Y-m-d',strtotime('+10 days')),'accounts'=>[['email'=>'child-sp@example.test','password'=>'synthetic-sp-secret','profiles'=>[['name'=>'']]]]];
 $spSave=http($adminBrowser,'gestion/spotify.php','POST',['_csrf'=>$csrf,'action'=>'save','payload'=>json_encode($spPayload)]);
 verify($spSave['status']===200,'Alta Spotify por HTTP');
 $spMain=json_decode($spSave['body'],true)['id'];
 verify(http($advisor,'gestion/spotify.php?action=main&id='.$spMain)['status']===403,'Datos principal solo administrador');
 $spList=json_decode(http($advisor,'gestion/spotify.php?action=list')['body'],true);verify($spList['total']===0&&!str_contains(json_encode($spList),'child-sp'),'Operativo no recibe inventario libre');
 $spObtain=['request_key'=>bin2hex(random_bytes(16)),'service_id'=>$spService,'phone'=>'+51999123456','client_name'=>'Cliente HTTP','start_date'=>date('Y-m-d')];
 $spAssigned=http($advisor,'gestion/spotify.php','POST',['_csrf'=>$advisorCsrf,'action'=>'obtain','payload'=>json_encode($spObtain)]);verify($spAssigned['status']===200&&json_decode($spAssigned['body'],true)['available'],'Asesor obtiene cuenta por HTTP');
 $spList=http($advisor,'gestion/spotify.php?action=list');$spRows=json_decode($spList['body'],true)['rows'];
 verify(count($spRows)===1&&$spRows[0]['profile']==='Perfil 1'&&$spRows[0]['password']==='synthetic-sp-secret','Operativo recibe sus credenciales');
 verify(!str_contains($spList['body'],'main-sp@')&&!str_contains($spList['body'],'payment-sp@')&&!str_contains($spList['body'],'next_payment'),'HTTP no expone proveedor al operativo');
 $db->execute_query("UPDATE fm_user_permissions SET allowed=0 WHERE user_id=? AND permission_code='services.spotify'",[$advisorId]);
 verify(http($advisor,'gestion/spotify.php?action=list')['status']===403,'Revocación Spotify bloquea sesión existente');
 // Rutas externas: autorización independiente y ninguna llamada real a la API.
 verify(http($probe,'servicios/generador.php?action=status')['status']===401,'Generador externo exige sesión');
 verify(http($advisor,'servicios/generador.php')['status']===403,'Generador requiere permiso propio');
 verify(http($adminBrowser,'mantenimiento/restricciones-generador.php')['status']===200,'Configuración externa administrativa');
 verify(http($adminBrowser,'reportes/links-externos.php?action=list')['status']===200,'Reporte externo administrativo');
 verify(http($adminBrowser,'servicios/generador.php','POST',['action'=>'generate','payload'=>'{}'])['status']===419,'Generador verifica CSRF');
 $db->execute_query("INSERT INTO fm_user_permissions VALUES(?,'services.external_links',1) ON DUPLICATE KEY UPDATE allowed=1",[$advisorId]);
 $status=http($advisor,'servicios/generador.php?action=status');verify($status['status']===200&&str_contains($status['headers'],'fm_external_browser='),'Generador establece cookie de navegador');
 verify(str_contains($status['headers'],'HttpOnly')||str_contains($status['headers'],'httponly'),'Cookie no accesible a scripts');
 verify(http($advisor,'mantenimiento/restricciones-generador.php')['status']===403,'Generador no concede configurar restricciones');
 verify(http($advisor,'reportes/links-externos.php')['status']===403,'Generador no concede reporte');
 // Clientes y reporte: autorización independiente y acceso HTTP real.
 verify(http($probe,'gestion/clientes.php?action=list')['status']===401,'Clientes exige sesión');
 verify(http($adminBrowser,'gestion/clientes.php')['status']===200,'Admin con permiso abre Clientes');
 verify(http($advisor,'gestion/clientes.php')['status']===403,'Asesor sin permiso no abre Clientes');
 verify(http($adminBrowser,'gestion/clientes.php','POST',['payload'=>'{}'])['status']===419,'Clientes exige CSRF');
 $saved=http($adminBrowser,'gestion/clientes.php','POST',['_csrf'=>$csrf,'payload'=>json_encode(['name'=>'Cliente Mantenimiento','phone'=>'+12025550666'])]);verify($saved['status']===200,'Alta de cliente HTTP');
 verify(http($probe,'gestion/clientes.php?action=information&phone=%2B12025550666')['status']===401,'Información cliente exige sesión');
 $clientInfo=http($adminBrowser,'gestion/clientes.php?action=information&phone=%2B12025550666');
 verify($clientInfo['status']===200&&!str_contains($clientInfo['body'],'password_cipher')&&!str_contains($clientInfo['body'],'payment_email'),'Información cliente HTTP autorizada sin datos sensibles');
 $duplicate=http($adminBrowser,'gestion/clientes.php','POST',['_csrf'=>$csrf,'payload'=>json_encode(['name'=>'Otro','phone'=>'+1 (202) 555-0666'])]);verify($duplicate['status']===409,'Duplicado HTTP normalizado');
 verify(http($advisor,'reportes/ventas-spotify.php?action=list')['status']===403,'Reporte independiente de Spotify');
 $db->execute_query("INSERT INTO fm_user_permissions(user_id,permission_code,allowed) VALUES(?,'reports.spotify_sales',1) ON DUPLICATE KEY UPDATE allowed=1",[$advisorId]);
 $r=http($advisor,'reportes/ventas-spotify.php?action=list&period=today');$body=json_decode($r['body'],true);verify($r['status']===200&&$body['totals']['sale']===1,'Reporte propio disponible sin permiso operativo');
 verify(http($adminBrowser,'reportes/ventas-spotify.php')['status']===200,'Reporte administrativo visible');
 foreach(['clients.manage'=>'gestion/clientes.php','reports.spotify_sales'=>'reportes/ventas-spotify.php'] as $code=>$path){
  $db->execute_query('INSERT INTO fm_user_permissions VALUES(?,?,0) ON DUPLICATE KEY UPDATE allowed=0',[$adminId,$code]);verify(http($adminBrowser,$path)['status']===403,'Administrador pierde acceso a '.$code);verify(!str_contains(http($adminBrowser,'home.php')['body'],'data-module="'.$path.'"'),'Menú oculta '.$code);$db->execute_query('DELETE FROM fm_user_permissions WHERE user_id=? AND permission_code=?',[$adminId,$code]);
 }
 $migrationCounts=$db->query('SELECT COUNT(*) n FROM uso_servicio')->fetch_assoc();
 foreach ([['--check'],['--apply','--allow-deployment'],['--apply','--allow-deployment']] as $flags) {
   $process=proc_open(array_merge([PHP_BINARY,FM_ROOT.'/bin/migrate-all.php'],$flags),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$migrationPipes,FM_ROOT,$env);
   fclose($migrationPipes[0]);$output=stream_get_contents($migrationPipes[1]);fclose($migrationPipes[1]);$error=stream_get_contents($migrationPipes[2]);fclose($migrationPipes[2]);
   verify(proc_close($process)===0,'Ejecutor de migraciones '.implode(' ',$flags).': '.$error);
 }
 verify($db->query('SELECT COUNT(*) n FROM uso_servicio')->fetch_assoc()===$migrationCounts,'Ejecutor repetido conserva todos los históricos');
 if(in_array('--browser',$argv,true)){
  $qaUsers=new \FMGlobal\Services\Users\UserService(new UserRepository($db),new ScheduleRepository($db));
  $qaAdmin=$qaUsers->save($seed('qa_admin',1),'test',$adminId);$qaAdvisor=$qaUsers->save($seed('qa_asesor',2),'test',$qaAdmin);$qaExternalRole=(int)$db->query("SELECT rol_id FROM rol WHERE rol_nombre='Externo'")->fetch_assoc()['rol_id'];$qaExternal=$qaUsers->save($seed('qa_externo',$qaExternalRole),'test',$qaAdmin);
  foreach(['services.spotify','reports.spotify_sales','clients.manage'] as $code)$db->execute_query('INSERT INTO fm_user_permissions VALUES(?,?,1)',[$qaAdvisor,$code]);
  $qaSpotify=new \FMGlobal\Repositories\SpotifyRepository($db,new \FMGlobal\Services\Links\AccountVault());$qaService=(int)$db->query("SELECT id FROM fm_service_types WHERE code='spotify'")->fetch_assoc()['id'];
  $qaSpotify->execute($qaAdmin,'save',['request_key'=>bin2hex(random_bytes(16)),'service_id'=>$qaService,'email'=>'qa-main@example.test','payment_email'=>'qa-pay@example.test','next_payment'=>'2027-01-01','accounts'=>[['email'=>'qa-one@example.test','password'=>'Synthetic only'],['email'=>'qa-two@example.test','password'=>'Synthetic only']]]);
  $node='C:/Users/itanc/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node.exe';
  $qaProcess=proc_open([$node,FM_ROOT.'/tests/browser/full-flow.mjs',$base,(string)$qaAdvisor,(string)$qaExternal],[0=>['pipe','r'],1=>['pipe','w'],2=>['file',$serverLog,'a']],$qaPipes,FM_ROOT,$env);fclose($qaPipes[0]);$qaOutput=stream_get_contents($qaPipes[1]);$qaError='Ver diagnóstico de Chrome';fclose($qaPipes[1]);echo $qaOutput;verify(proc_close($qaProcess)===0,'Recorridos Chrome reales: '.$qaError);
 }
 echo "$count comprobaciones funcionales correctas en base temporal, sin consultas externas.\n";
}finally{
 foreach($clients as $client)curl_close($client);
 if(is_resource($server)){proc_terminate($server);proc_close($server);}
 if($db)$db->close();
 if(preg_match('/^fmglobal_test_[a-f0-9]{12}$/D',$testDb))$admin->query("DROP DATABASE IF EXISTS `$testDb`");
 $admin->close();if(is_file($serverLog))unlink($serverLog);
}
