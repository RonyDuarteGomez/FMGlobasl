<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/bootstrap/app.php';
use FMGlobal\Http\HttpException;
use FMGlobal\Security\{Access,Csrf,OAuthState,Session};
use FMGlobal\Services\Http\MailApiClient;
use FMGlobal\Services\Users\ScheduleService;
$count=0;
function check(bool $ok,string $message):void{global $count;if(!$ok)throw new RuntimeException($message);$count++;}
function rejects(callable $fn,int $status,string $message):void{try{$fn();}catch(HttpException $e){check($e->status===$status,$message);return;}throw new RuntimeException($message);}
Session::start();
$csrf=Csrf::token(); $_POST['_csrf']=$csrf; Csrf::verify(); check(strlen($csrf)===64,'Token CSRF aleatorio');
$_POST['_csrf']='incorrecto';rejects(fn()=>Csrf::verify(),419,'Rechazar CSRF inválido');
$targetState=OAuthState::create("fixture@example.test");check(OAuthState::consume($targetState)==="fixture@example.test","OAuth conserva correo esperado en la sesion");
$state=OAuthState::create();OAuthState::consume($state);rejects(fn()=>OAuthState::consume($state),400,'OAuth no admite reuso');
$state=OAuthState::create();$_SESSION['gmail_oauth_state']['expires']=time()-1;rejects(fn()=>OAuthState::consume($state),400,'OAuth vencido');
check(\FMGlobal\Security\PermissionCatalog::route('usuario/usuario_save.php')==='users.manage','Usuarios solo admin');
check(\FMGlobal\Security\PermissionCatalog::route('soporte/procesar_netflix1.php')==='services.advisor','Consultas de asesor');
check(\FMGlobal\Security\PermissionCatalog::route('soporte/procesar_soporte.php')==='services.support','Consultas de soporte');
check(ScheduleService::pair('09','19')===['09:00:00','19:00:00'],'Normalizar horas');
check(ScheduleService::pair('00','00')===['00:00:00','00:00:00'],'Conservar día cerrado');
rejects(fn()=>ScheduleService::pair('24','25'),422,'Horas fuera de rango');
rejects(fn()=>ScheduleService::pair('19','09'),422,'Horario invertido');
$url='https://fmglobals.com/api/netflix_otp.php?correo=test@example.com';
$client=fn($status,$body)=>new MailApiClient(fn()=>['status'=>$status,'body'=>$body]);
check($client(200,'{"ok":true,"data":[]}')->get($url)['data']===[],'Buzón vacío es éxito');
rejects(fn()=>$client(500,'secret')->get($url),502,'Error HTTP');
rejects(fn()=>$client(200,'<html>secret</html>')->get($url),502,'Respuesta no JSON');
rejects(fn()=>$client(200,'{"ok":false,"message":"secret"}')->get($url),502,'Error del proveedor');
rejects(fn()=>$client(200,'{"ok":true,"data":[{}]}')->get($url),502,'Registros inválidos');
rejects(fn()=>(new MailApiClient(fn()=>throw new HttpException(504,'Tiempo agotado.')))->get($url),504,'Timeout diferenciado');
check($client(200,'{"ok":false,"message":"No se encontraron correos"}')->get($url)['data']===[],'Compatibilidad con API anterior');
try{$client(200,'{}')->get('https://otro.example/api/netflix_otp.php');throw new RuntimeException('Endpoint externo aceptado');}catch(InvalidArgumentException $e){$count++;}
$fake=new class implements \FMGlobal\Contracts\MailProvider{public array $result=['ok'=>true,'total'=>0,'data'=>[]];public function buscarCorreos($correo,$password,$minutes=15):array{return $this->result;}};
$mail=new CorreoService(new \FMGlobal\Services\Mail\ProviderRegistry(['test'=>fn()=>$fake]));
check($mail->buscarCorreos('test','a@gmail.com','')['ok'],'Registro extensible respeta proveedor explícito incluso con Gmail');
rejects(fn()=>$mail->buscarCorreos('unknown','a@gmail.com',''),422,'Proveedor desconocido');
rejects(fn()=>$mail->buscarCorreos('test','correo-invalido',''),422,'Correo inválido');
rejects(fn()=>$mail->buscarCorreos('test','a@gmail.com','',-1),422,'Intervalo inválido');
foreach([new NetflixOtpService($mail),new NetflixLinkService($mail),new DisneyOtpService($mail)] as $service){
 $method=$service instanceof NetflixLinkService?'buscarLinks':'buscarOtp';
 $fake->result=['ok'=>true,'data'=>[]];check($service->$method('test','a@example.com')['ok'],'Extracción vacía es éxito');
 $fake->result=['ok'=>false,'message'=>'Servicio no disponible'];check($service->$method('test','a@example.com')['message']==='Servicio no disponible','Extracción conserva error');
}
$valid='{"ok":true,"data":[{"date":"2026-09-19 10:00:00","codigo":"123456"}]}';
check($client(200,$valid)->get($url)['data'][0]['codigo']==='123456','Respuesta OTP válida');
rejects(fn()=>$client(200,'{"ok":true,"data":[{"date":"2026-09-19","link":"javascript:alert(1)"}]}')->get('https://fmglobals.com/api/netflix_link.php'),502,'Enlace no HTTP rechazado');
$hours=[['dia'=>'LUNES','hora_inicio'=>'09:00:00','hora_fin'=>'18:00:00']];
$at=fn($time)=>new DateTimeImmutable('2026-09-21 '.$time,new DateTimeZone('America/Lima'));
check(\FMGlobal\Services\Reports\PublicAvailability::current($hours,$at('08:59'))===true,'Activación vigente antes del bloqueo');
check(\FMGlobal\Services\Reports\PublicAvailability::current($hours,$at('09:00'))===false,'Inicio del bloqueo incluido');
check(\FMGlobal\Services\Reports\PublicAvailability::current($hours,$at('17:59'))===false,'Activación no vigente durante bloqueo');
check(\FMGlobal\Services\Reports\PublicAvailability::current($hours,$at('18:00'))===true,'Fin del bloqueo excluido');
check(\FMGlobal\Services\Reports\PublicAvailability::current([],$at('12:00'))===null,'Sin configuración horaria');
check(\FMGlobal\Services\Reports\PublicAvailability::current([['dia'=>'LUNES','hora_inicio'=>'00:00:00','hora_fin'=>'00:00:00']],$at('12:00'))===true,'Intervalo vacío no bloquea activación pública');
check(str_contains(\FMGlobal\Support\DailyBars::render([]),'Sin actividad'),'Gráfico sin registros no inventa barras');
$chart=\FMGlobal\Support\DailyBars::render(['2026-09-18'=>0,'2026-09-19'=>3]);
check(substr_count($chart,'<rect')===1 && str_contains($chart,'2026-09-19: 3'),'Gráfico representa únicamente días con actividad real');
$menu=\FMGlobal\Support\Navigation::groups(['services.support'=>true,'reports.support'=>true,'activity.own'=>true]);
check(count($menu['Reportes'])===1 && $menu['Reportes'][0][2]==='dashboard.php?report=support','Menú soporte abre solo su reporte autorizado');
check(!isset(\FMGlobal\Support\Navigation::groups(['services.support'=>true])['Reportes']),'Reportes requieren permiso de visualizar actividad');
$week=\FMGlobal\Services\Schedules\WeeklySchedule::legacy([['dia'=>'LUNES','hora_inicio'=>'09:00:00','hora_fin'=>'18:00:00']]);
check(count($week)===7 && count($week[1]['slots'])===2,'Migración semanal conserva dos períodos activos');
check(\FMGlobal\Services\Schedules\WeeklySchedule::active($week,$at('08:59')) && !\FMGlobal\Services\Schedules\WeeklySchedule::active($week,$at('09:00')) && \FMGlobal\Services\Schedules\WeeklySchedule::active($week,$at('18:00')),'Límites de intervalo activo coherentes con bloqueo anterior');
$week[1]=['mode'=>'hours','slots'=>[['start'=>'08:15','end'=>'10:30'],['start'=>'23:00','end'=>'24:00']]];
$week=\FMGlobal\Services\Schedules\WeeklySchedule::validate($week);
check(!\FMGlobal\Services\Schedules\WeeklySchedule::active($week,$at('08:14')) && \FMGlobal\Services\Schedules\WeeklySchedule::active($week,$at('08:15')) && !\FMGlobal\Services\Schedules\WeeklySchedule::active($week,$at('10:30')) && \FMGlobal\Services\Schedules\WeeklySchedule::active($week,$at('23:59')),'Soporta minutos, cierre exacto y final del día');
rejects(fn()=>\FMGlobal\Services\Schedules\WeeklySchedule::validate([]),422,'Exige siete días');
rejects(fn()=>\FMGlobal\Services\Schedules\WeeklySchedule::minute('24:00'),422,'24:00 no es inicio válido');
rejects(fn()=>\FMGlobal\Services\Schedules\WeeklySchedule::minute('09:60'),422,'Minutos inválidos');
Session::clear();
Session::start();
$_SESSION['usuario_id']=123;
$_SESSION['last_activity']=time()-1700;
Session::checkIdle();
check(isset($_SESSION['usuario_id']),'Sesion valida antes de 30 minutos');
Session::touch();
check($_SESSION['last_activity']>=time()-1,'Actividad renueva plazo');
$_SESSION['last_activity']=time()-Session::IDLE_SECONDS;
rejects(fn()=>Session::checkIdle(),401,'Sesion vence al cumplir 30 minutos');
check(empty($_SESSION),'Vencimiento elimina identidad y CSRF');
$throttleFile=tempnam(sys_get_temp_dir(),'fm-throttle-');
try {
 $limiter=new \FMGlobal\Security\LoginThrottle($throttleFile);
 for($attempt=0;$attempt<10;$attempt++)$limiter->attempt('Cuenta','192.0.2.1',1000);
 rejects(fn()=>$limiter->attempt('CUENTA','192.0.2.1',1001),429,'Limite de login no se evade cambiando mayusculas');
 $limiter->attempt('Cuenta','192.0.2.1',1900);
 check(true,'Limite de login se libera tras 15 minutos');
 check(!str_contains(file_get_contents($throttleFile),'Cuenta') && !str_contains(file_get_contents($throttleFile),'192.0.2.1'),'Contador no guarda usuario ni IP en texto');
} finally {unlink($throttleFile);}
echo "$count comprobaciones unitarias correctas, sin conexiones externas.\n";
