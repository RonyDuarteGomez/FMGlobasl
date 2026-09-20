<?php
use FMGlobal\Repositories\LinkAccountRepository;
use FMGlobal\Services\Links\{AccountVault,AccountImport,LinkGenerator};
use FMGlobal\Http\HttpException;
$accounts=new LinkAccountRepository(database(),new AccountVault());
$actor=(int)$_SESSION['usuario_id'];$isAdmin=$accounts->actor($actor)['admin'];
$action=$_SERVER['REQUEST_METHOD']==='POST'?($_POST['action']??''):($_GET['action']??'');
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='') {require FM_ROOT.'/resources/views/Links/index.php';return;}
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='template') {
 if(!$isAdmin)throw new HttpException(403,'Acción exclusiva del administrador.');
 header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="modelo-cuentas-link.csv"');echo "\xEF\xBB\xBFID,secure,correo_contrasena\r\n";return;
}
header('Content-Type: application/json; charset=UTF-8');
if($_SERVER['REQUEST_METHOD']==='GET') {
 $result=match($action){
  'list'=>$accounts->listing($actor,$_GET)+['summary'=>$accounts->summary($actor),'users'=>$isAdmin?$accounts->users($actor):[]],
  'details'=>$accounts->details($actor,(int)($_GET['id']??0)),
  default=>throw new HttpException(422,'Acción no válida.'),
 };
}else {
 $result=match($action){
  'save'=>['id'=>$accounts->save($actor,$_POST),'message'=>'Cuenta guardada.'],
  'preview'=>$accounts->preview($actor,$_POST),
  'move'=>$accounts->move($actor,$_POST),
  'error'=>(function()use($accounts,$actor){$accounts->markError($actor,(int)($_POST['id']??0),(int)($_POST['revision']??0));return ['message'=>'Cuenta marcada con Error.'];})(),
  'import'=>(function()use($accounts,$actor){$file=$_FILES['csv']??null;if(!$file||$file['error']!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']))throw new HttpException(422,'Selecciona un CSV válido (máximo 5 MB).');return (new AccountImport($accounts))->run($actor,$file['tmp_name']);})(),
  'generate'=>(function()use($accounts,$actor){$id=(int)($_POST['id']??0);$values=$accounts->beginGeneration($actor,$id);try{$result=(new LinkGenerator())->generate($values['external_id'],$values['secure']);}catch(\Throwable $e){$accounts->finishGeneration($actor,$id,$values['token'],false);throw $e;}$accounts->finishGeneration($actor,$id,$values['token'],true);return $result;})(),
  default=>throw new HttpException(422,'Acción no válida.'),
 };
}
echo json_encode(['ok'=>true]+$result,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
