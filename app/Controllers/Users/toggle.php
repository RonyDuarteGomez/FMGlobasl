<?php
$service=new \FMGlobal\Services\Users\UserService(new \FMGlobal\Repositories\UserRepository(database()),new \FMGlobal\Repositories\ScheduleRepository(database()));
$state=$service->toggle(\FMGlobal\Support\Input::id($_POST,'usuario_id'),(int)$_SESSION['usuario_id']);
echo $state===1?'Usuario activado':'Usuario inactivado';
