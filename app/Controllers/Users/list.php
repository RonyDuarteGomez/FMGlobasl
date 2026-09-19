<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    echo "No autorizado";
    exit();
}

$conexion = database();

$sql = "
SELECT 
    u.id AS usuario_id,
    u.usuario,
    u.estado,
    p.id AS personal_id,
    p.nombre,
    p.apellido_paterno,
    p.apellido_materno,
    p.correo,
    p.telefono,
    r.rol_nombre AS cargo
FROM usuarios u
LEFT JOIN personal p ON u.id = p.usuario_id
LEFT JOIN rol r ON p.rol_id = r.rol_id
WHERE u.id <> 1
ORDER BY r.rol_id, p.nombre, p.apellido_paterno, p.apellido_materno DESC
";

$result = $conexion->query($sql);

require FM_ROOT . '/resources/views/Users/list.php';
