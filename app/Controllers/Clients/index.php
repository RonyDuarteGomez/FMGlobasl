<?php
use FMGlobal\Repositories\ClientsRepository;
use FMGlobal\Http\HttpException;
$actor=(int)$_SESSION['usuario_id'];$repo=new ClientsRepository(database());$repo->authorize($actor);
if($_SERVER['REQUEST_METHOD']==='GET'&&!isset($_GET['action'])){require FM_ROOT.'/resources/views/Clients/index.php';return;}
if($_SERVER['REQUEST_METHOD']==='GET'&&($_GET['action']??'')==='template'){
 header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="clientes-modelo.csv"');echo "\xEF\xBB\xBFnombre,celular\r\nCliente de ejemplo,+51987654321\r\n";return;
}
header('Content-Type: application/json; charset=UTF-8');
if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='import'){
 $file=$_FILES['file']??null;
 if(!is_array($file)||($file['error']??-1)!==UPLOAD_ERR_OK||!is_string($file['tmp_name']??null)||!is_uploaded_file($file['tmp_name']))throw new HttpException(422,'Selecciona un CSV válido.');
 if(filesize($file['tmp_name'])>2097152||strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))!=='csv')throw new HttpException(422,'Usa un archivo .csv de hasta 2 MB.');
 echo json_encode(['ok'=>true]+$repo->importFile($actor,file_get_contents($file['tmp_name'])),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);return;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{$input=json_decode($_POST['payload']??'',true,32,JSON_THROW_ON_ERROR);}catch(\Throwable $e){throw new HttpException(422,'Solicitud no válida.');}
 if(!is_array($input))throw new HttpException(422,'Solicitud no válida.');$result=$repo->save($actor,$input);
}else{$result=match($_GET['action']??''){'list'=>$repo->listing($actor,$_GET),'information'=>$repo->information($actor,$_GET),default=>throw new HttpException(422,'Acción no válida.')};}
echo json_encode(['ok'=>true]+$result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
