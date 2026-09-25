<?php
$action=$_GET['action']??$_POST['action']??'';
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='template'){
 header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="usuarios-modelo.csv"');
 echo "\xEF\xBB\xBF".implode(',',\FMGlobal\Services\Users\CsvImport::COLUMNS)."\r\n";
 echo "Persona,Ejemplo,,persona@example.com,+51987654321,persona_ejemplo,Ejemplo123!,Asesor\r\n";return;
}
if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='import'){
 $file=$_FILES['file']??null;
 if(!is_array($file)||($file['error']??-1)!==UPLOAD_ERR_OK||!is_string($file['tmp_name']??null)||!is_uploaded_file($file['tmp_name']))throw new \FMGlobal\Http\HttpException(422,'Selecciona un CSV válido.');
 if(filesize($file['tmp_name'])>2097152||strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))!=='csv')throw new \FMGlobal\Http\HttpException(422,'Usa un archivo .csv de hasta 2 MB.');
 $result=(new \FMGlobal\Services\Users\CsvImport(database()))->import((int)$_SESSION['usuario_id'],$_SESSION['usuario'],file_get_contents($file['tmp_name']));
 header('Content-Type: application/json; charset=UTF-8');echo json_encode(['ok'=>true]+$result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);return;
}
if($action!=='')throw new \FMGlobal\Http\HttpException(422,'Acción no válida.');

$result=(new \FMGlobal\Repositories\UserRepository(database()))->roles();
require FM_ROOT.'/resources/views/Users/index.php';
