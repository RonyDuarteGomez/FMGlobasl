<?php
if ($_SERVER['REQUEST_METHOD']==='POST') {
    // The browser reports a successful generation. Never store cookies or login URLs.
    if (($_POST['event']??null)!=='generated') throw new \FMGlobal\Http\HttpException(422,'Evento no válido.');
    (new \FMGlobal\Repositories\UsageRepository(database()))->register('',1,$_SESSION['usuario'],7);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>true]);
    return;
}
require FM_ROOT . '/resources/views/Links/index.php';