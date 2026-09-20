<?php

\FMGlobal\Security\Session::start();

if (!isset($_SESSION['usuario'])) {

  header("Location: login.php");

  exit();
}

$conexion = database();

date_default_timezone_set('America/Lima');

// =====================================
// CONSULTA CORREOS AUTORIZADOS
// =====================================

$repository = new \FMGlobal\Repositories\GmailTokenRepository($conexion);
if (($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
    $repository->deactivate(\FMGlobal\Support\Input::id($_POST,'id'));
    header('Location: home.php?modulo=Autoriza',true,303);
    exit;
}
$result = $repository->listAuthorized();

require FM_ROOT . '/resources/views/Gmail/index.php';
