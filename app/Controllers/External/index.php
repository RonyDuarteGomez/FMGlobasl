<?php
use FMGlobal\Repositories\ExternalLinkRepository;
use FMGlobal\Http\HttpException;
$actor=(int)$_SESSION['usuario_id'];$repo=new ExternalLinkRepository(database());
$mode=str_starts_with($route,'mantenimiento/')?'rules':(str_starts_with($route,'reportes/')?'report':'generate');
$permission=['rules'=>'external.restrictions','report'=>'reports.external_links','generate'=>'services.external_links'][$mode];$repo->authorize($actor,$permission);
$token='';if($mode==='generate'){$token=$_COOKIE['fm_external_browser']??'';if(!is_string($token)||!preg_match('/^[a-f0-9]{64}$/D',$token)){$token=bin2hex(random_bytes(32));setcookie('fm_external_browser',$token,['expires'=>time()+31536000,'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Strict']);}}
$action=$_SERVER['REQUEST_METHOD']==='POST'?($_POST['action']??''):($_GET['action']??'');
if($_SERVER['REQUEST_METHOD']==='GET'&&$action===''){require FM_ROOT.'/resources/views/External/index.php';return;}
header('Content-Type: application/json; charset=UTF-8');
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{$input=json_decode($_POST['payload']??'',true,32,JSON_THROW_ON_ERROR);}catch(\Throwable $e){throw new HttpException(422,'Solicitud no válida.');}if(!is_array($input))throw new HttpException(422,'Solicitud no válida.');
 $result=match($mode.':'.$action){'generate:status'=>$repo->status($actor,$token,session_id(),true,mb_substr($_SERVER['HTTP_USER_AGENT']??'Navegador',0,200)),'rules:save'=>$repo->save($actor,$input),'rules:browser'=>$repo->browserAction($actor,$input),'generate:register_browser'=>$repo->registerBrowser($actor,$token,session_id(),mb_substr($_SERVER['HTTP_USER_AGENT']??'Navegador',0,200)),'generate:request_browser'=>$repo->requestBrowser($actor,$token,mb_substr($_SERVER['HTTP_USER_AGENT']??'Navegador',0,200)),'generate:generate'=>$repo->generate($actor,$input,$token,session_id()),default=>throw new HttpException(422,'Acción no válida.')};
}else{$result=match($mode.':'.$action){'rules:config'=>$repo->configuration($actor,$_GET['type']??'role',FMGlobal\Services\Spotify\Rules::id($_GET['id']??1)),'generate:status'=>$repo->status($actor,$token,session_id()),'report:list'=>$repo->report($actor,$_GET),default=>throw new HttpException(422,'Acción no válida.')};}
echo json_encode(['ok'=>true]+$result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
