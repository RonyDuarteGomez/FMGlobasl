<?php
$conexion = database();

$id          = $_POST['id'] ?? '';
$dia         = $_POST['dia'] ?? '';
$hora_inicio = $_POST['hora_inicio'] ?? '';
$hora_fin    = $_POST['hora_fin'] ?? '';
$usuario     = 'admin'; // o el usuario actual

if (!$id || !$dia) {
  echo "0";
  exit;
}

// 🔹 Convertimos "19" → "19:00:00"
$hora_inicio = str_pad($hora_inicio, 2, "0", STR_PAD_LEFT) . ":00:00";
$hora_fin    = str_pad($hora_fin, 2, "0", STR_PAD_LEFT) . ":00:00";

$stmt = $conexion->prepare("
  UPDATE activacion
  SET hora_inicio = ?, hora_fin = ?, usuario_registro = ?, fecha_registro = NOW()
  WHERE id = ?
");

$stmt->bind_param("sssi", $hora_inicio, $hora_fin, $usuario, $id);

echo $stmt->execute() ? "1" : "0";
