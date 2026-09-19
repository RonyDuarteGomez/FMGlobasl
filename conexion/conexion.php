<?php
// conexion.php
$host = "localhost";        // Servidor de la base de datos
$user = "fmglobal_fmglobal";             // Usuario de la base de datos
$pass = (require __DIR__ . '/../config/private.php')['db_password'];                 // Contraseña de la base de datos
$db   = "fmglobal_streaming";        // Nombre de la base de datos
//$host = "localhost";        // Servidor de la base de datos
//$user = "root";             // Usuario de la base de datos
//$pass = "";                 // Contraseña de la base de datos
//$db   = "fmglobal_streaming";        // Nombre de la base de datos

$conexion = new mysqli($host, $user, $pass, $db);

// Revisar conexión
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Configurar charset a utf8
$conexion->set_charset("utf8");
