<?php
$conexion = database();

// Obtener todas las configuraciones (lunes a domingo)
$query = "SELECT id, dia, hora_inicio, hora_fin FROM activacion where usuario_id = '0' ORDER BY 
    CASE 
        WHEN dia = 'LUNES' THEN 1
        WHEN dia = 'MARTES' THEN 2
        WHEN dia = 'MIERCOLES' THEN 3
        WHEN dia = 'JUEVES' THEN 4
        WHEN dia = 'VIERNES' THEN 5
        WHEN dia = 'SABADO' THEN 6
        WHEN dia = 'DOMINGO' THEN 7
        ELSE 8
    END";
$result = $conexion->query($query);

// Hora y día actual del servidor
date_default_timezone_set('America/Lima');
$hora_actual = (int)date('H');
$dia_actual = strtoupper(date('l')); // Ejemplo: MONDAY
$mapa_dias = [
  'MONDAY' => 'LUNES',
  'TUESDAY' => 'MARTES',
  'WEDNESDAY' => 'MIERCOLES',
  'THURSDAY' => 'JUEVES',
  'FRIDAY' => 'VIERNES',
  'SATURDAY' => 'SABADO',
  'SUNDAY' => 'DOMINGO'
];
$dia_actual = $mapa_dias[$dia_actual] ?? '';


require FM_ROOT . '/resources/views/Schedules/index.php';
