<?php

session_start();

if (!isset($_SESSION['usuario'])) {

  header("Location: login.php");

  exit();
}

$conexion = database();

date_default_timezone_set('America/Lima');

// =====================================
// CONSULTA CORREOS AUTORIZADOS
// =====================================

$result = (new \FMGlobal\Repositories\GmailTokenRepository($conexion))->listAuthorized();

require FM_ROOT . '/resources/views/Gmail/index.php';
