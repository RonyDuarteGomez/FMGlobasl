<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit();
}

$usuario = $_SESSION['usuario'];
$nombre = isset($_SESSION['nombre_completo']) ? $_SESSION['nombre_completo'] : $usuario;


$conexion = database();
date_default_timezone_set('America/Lima');



$dashboard = new \FMGlobal\Services\Reports\DashboardData(new \FMGlobal\Repositories\UsageRepository($conexion));
extract($dashboard->build(), EXTR_SKIP);

require FM_ROOT . '/resources/views/Dashboard/home.php';
