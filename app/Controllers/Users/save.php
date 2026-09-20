<?php
$service=new \FMGlobal\Services\Users\UserService(new \FMGlobal\Repositories\UserRepository(database()),new \FMGlobal\Repositories\ScheduleRepository(database()));
$service->save($_POST,$_SESSION['usuario'],(int)$_SESSION['usuario_id']);
echo 'Usuario guardado correctamente';
