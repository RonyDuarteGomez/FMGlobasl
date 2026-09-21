<?php
use FMGlobal\Repositories\SpotifyRepository;
use FMGlobal\Services\Spotify\Rules;
use FMGlobal\Services\Links\AccountVault;
use FMGlobal\Http\HttpException;
$repo=new SpotifyRepository(database(),new AccountVault());$actor=(int)$_SESSION['usuario_id'];$isAdmin=$repo->actor($actor)['admin'];
$action=$_GET['action']??'';
if($_SERVER['REQUEST_METHOD']==='GET'&&$action===''){require FM_ROOT.'/resources/views/Spotify/index.php';return;}
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='template'){
 if(!$isAdmin)throw new HttpException(403,'Acción exclusiva del administrador.');
 header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="spotify-modelo.csv"');readfile(FM_ROOT.'/docs/templates/spotify-modelo.csv');return;
}
header('Content-Type: application/json; charset=UTF-8');
if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='import'){
 if(!$isAdmin)throw new HttpException(403,'Acción exclusiva del administrador.');
 $file=$_FILES['file']??null;
 if(!is_array($file)||($file['error']??-1)!==UPLOAD_ERR_OK||!is_string($file['tmp_name']??null)||!is_uploaded_file($file['tmp_name']))throw new HttpException(422,'Selecciona un CSV válido (máximo 2 MB).');
 if(filesize($file['tmp_name'])>2097152||strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))!=='csv')throw new HttpException(422,'Usa un archivo .csv de hasta 2 MB.');
 $key=$_POST['request_key']??'';if(!is_string($key))throw new HttpException(422,'Solicitud no válida.');
 $result=$repo->importFile($actor,file_get_contents($file['tmp_name']),$key);
 echo json_encode(['ok'=>true]+$result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);return;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
 $payload=$_POST['payload']??'';if(!is_string($payload))throw new HttpException(422,'Solicitud no válida.');
 try{$data=json_decode($payload,true,32,JSON_THROW_ON_ERROR);}catch(\JsonException $e){throw new HttpException(422,'Solicitud no válida.');}
 if(!is_array($data)||!is_string($_POST['action']??null))throw new HttpException(422,'Solicitud no válida.');
 $result=$repo->execute($actor,$_POST['action'],$data);
}else{
 $result=match($action){'payments'=>$repo->payments($actor,$_GET),'list'=>$repo->listing($actor,$_GET),'metadata'=>$repo->metadata($actor),'main'=>['main'=>$repo->main($actor,Rules::id($_GET['id']??null))],'client'=>['client'=>$repo->client($actor,Rules::text($_GET['phone']??'',40))],'assigned'=>['assignment'=>$repo->assignedDetails($actor,Rules::id($_GET['id']??null))],default=>throw new HttpException(422,'Acción no válida.')};
}
echo json_encode(['ok'=>true]+$result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
