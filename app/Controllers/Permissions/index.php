<?php
use FMGlobal\Support\Input;
$type=Input::text($_GET,'type',10)?:'role';
if (!in_array($type,['user','role'],true)) throw new \FMGlobal\Http\HttpException(422,'Tipo no válido.');
$db=database();
$targets=$type==='role'?$db->query('SELECT rol_id AS id,rol_nombre AS label FROM rol ORDER BY rol_id')->fetch_all(MYSQLI_ASSOC):$db->query("SELECT u.id,CONCAT(COALESCE(NULLIF(TRIM(CONCAT_WS(' ',p.nombre,p.apellido_paterno,p.apellido_materno)),''),u.usuario),' (',COALESCE(r.rol_nombre,'Sin perfil'),')') AS label FROM usuarios u LEFT JOIN personal p ON p.usuario_id=u.id LEFT JOIN rol r ON r.rol_id=p.rol_id WHERE NOT (u.id=1 AND u.usuario='admin') ORDER BY label")->fetch_all(MYSQLI_ASSOC);
$id=isset($_GET['id'])?Input::id($_GET,'id'):(int)($targets[0]['id']??0);
$repo=new \FMGlobal\Repositories\PermissionRepository($db);
$selected=array_values(array_filter($targets,fn($row)=>(int)$row['id']===$id));
if (!$selected) throw new \FMGlobal\Http\HttpException(404,'Usuario o perfil no encontrado.');
$revision=$repo->revision();
$values=$repo->settings($type,$id);
$roleId=$type==='role'?$id:(int)(new \FMGlobal\Repositories\UserRepository($db))->identity($id)['rol_id'];
$inherited=$repo->settings('role',$roleId);
$effective=$type==='user'?$repo->effective($id):[];

$catalog=\FMGlobal\Security\PermissionCatalog::ITEMS;
require FM_ROOT.'/resources/views/Permissions/index.php';
