<?php
use FMGlobal\Support\Input;
$id=Input::id($_GET,'usuario_id');
$row=(new \FMGlobal\Repositories\UserRepository(database()))->details($id,Input::id($_GET,'personal_id'));
if (!$row) throw new \FMGlobal\Http\HttpException(404,'Usuario no encontrado.');
$horarios=(new \FMGlobal\Services\Users\ScheduleService(new \FMGlobal\Repositories\ScheduleRepository(database())))->indexed($id);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['success'=>true,'usuario'=>['id'=>$row['usuario_id'],'usuario'=>$row['usuario'],'estado'=>$row['estado']], 'personal'=>['id'=>$row['personal_id'],'nombre'=>$row['nombre'],'apellido_paterno'=>$row['apellido_paterno'],'apellido_materno'=>$row['apellido_materno'],'correo'=>$row['correo'],'telefono'=>$row['telefono'],'celular'=>$row['celular'],'cargo'=>$row['cargo'],'rol'=>(int)$row['rol_id']],'horarios'=>$horarios],JSON_THROW_ON_ERROR);
