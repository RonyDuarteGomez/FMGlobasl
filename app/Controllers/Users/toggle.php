<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    echo "No autorizado";
    exit();
}

$conexion = database();

$usuario_id = intval($_POST['usuario_id']);

// Obtener estado actual
$sql = "SELECT estado FROM usuarios WHERE id=?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $nuevo_estado = ($row['estado'] == 1) ? 0 : 1;

    $sqlUpdate = "UPDATE usuarios SET estado=? WHERE id=?";
    $stmt = $conexion->prepare($sqlUpdate);
    $stmt->bind_param("ii", $nuevo_estado, $usuario_id);
    $stmt->execute();

    echo ($nuevo_estado == 1) ? "Usuario activado" : "Usuario inactivado";
} else {
    echo "Usuario no encontrado";
}
