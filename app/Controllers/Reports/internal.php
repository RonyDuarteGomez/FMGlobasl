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
  CONCAT(
      UCASE(LEFT(pe.nombre,1)), LCASE(SUBSTRING(pe.nombre,2)), ' ',
      UCASE(LEFT(pe.apellido_paterno,1)), LCASE(SUBSTRING(pe.apellido_paterno,2)), ' ',
      UCASE(LEFT(pe.apellido_materno,1)), LCASE(SUBSTRING(pe.apellido_materno,2))
    ) as usuario,
  CASE 
    WHEN us.streaming = '3' THEN 'Netflix (Estoy de Viaje)'
    WHEN us.streaming = '4' THEN 'Disney (Acceso Unico)'
    WHEN us.streaming = '5' THEN 'Netflix (Inicio Session)'
    WHEN us.streaming = '6' THEN 'Soporte'
    ELSE 'N/D'
  END AS servicio
FROM uso_servicio us
LEFT JOIN usuarios usu ON us.usuario = usu.usuario
LEFT JOIN personal pe ON usu.id = pe.usuario_id
WHERE us.streaming IN ('3','4','5','6')
ORDER BY us.fecha DESC
";
$result = $conexion->query($sql);

require FM_ROOT . '/resources/views/Reports/internal.php';
