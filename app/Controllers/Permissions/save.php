<?php
use FMGlobal\Support\Input;
use FMGlobal\Http\HttpException;
$type=Input::text($_POST,'type',10,true);
if (!in_array($type,['user','role'],true)) throw new HttpException(422,'Tipo no válido.');
$id=Input::id($_POST,'id');
$revision=$_POST['revision']??null;
if (!is_scalar($revision) || filter_var($revision,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]])===false) throw new HttpException(422,'Versión no válida.');
$values=$_POST['permissions']??null;
$catalog=\FMGlobal\Security\PermissionCatalog::ITEMS;
if (!is_array($values) || array_diff(array_keys($values),array_keys($catalog)) || array_diff(array_keys($catalog),array_keys($values))) throw new HttpException(422,'Envía la lista completa de permisos.');
foreach($values as $value) if (!in_array($value,$type==='user'?['inherit','allow','deny']:['allow','deny'],true)) throw new HttpException(422,'Valor de permiso no válido.');
(new \FMGlobal\Repositories\PermissionRepository(database()))->save($type,$id,$values,(int)$_SESSION['usuario_id'],(int)$revision);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['ok'=>true,'message'=>'Permisos guardados. Los accesos se actualizarán al cargar la siguiente pantalla.']);
