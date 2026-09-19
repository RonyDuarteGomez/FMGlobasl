<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit();
}
$conexion = database();
date_default_timezone_set('America/Lima');

// ==============================
// 1. CONSULTA: Detalle de uso
// ==============================
$sql = "
SELECT 
  DATE(us.fecha) AS fecha_consulta,
  us.correo AS correo_consultado,
  us.num_urls AS n_codigos,
  CASE WHEN streaming = '1' THEN 'Netflix' ELSE 'Disney'END AS usuario
FROM uso_servicio us
LEFT JOIN usuarios usu ON us.usuario = usu.usuario
LEFT JOIN personal pe ON usu.id = pe.usuario_id
where streaming in ('1','2')
ORDER BY us.fecha DESC;

";
$result = $conexion->query($sql);

// ==============================
// 2. CONSULTA: Totales por día (para gráfico)
// ==============================
$sqlGrafico = "
SELECT 
  DATE(fecha) AS fecha,
  COUNT(*) AS total_consultas
FROM uso_servicio
where streaming in ('1','2')
GROUP BY DATE(fecha)
ORDER BY fecha ASC
";
$resGrafico = $conexion->query($sqlGrafico);

$labels = [];
$valores = [];
while ($row = $resGrafico->fetch_assoc()) {
  $labels[] = $row['fecha'];
  $valores[] = $row['total_consultas'];
}

require FM_ROOT . '/resources/views/Reports/public.php';
