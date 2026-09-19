<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$conexion = database();

$sql = "SELECT rol_id, rol_nombre FROM rol";

$result = $conexion->query($sql);




require FM_ROOT . '/resources/views/Users/index.php';
