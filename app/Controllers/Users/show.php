<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$conexion = database();

$usuario_id  = intval($_GET['usuario_id']);
$personal_id = intval($_GET['personal_id']);

$sql = "SELECT u.id AS usuario_id, u.usuario, u.estado,
               p.id AS personal_id, p.nombre, p.apellido_paterno, p.apellido_materno, 
               p.correo, p.telefono, p.celular, p.cargo, r.rol_id
        FROM usuarios u
        LEFT JOIN personal p ON u.id = p.usuario_id
        LEFT JOIN rol r ON p.rol_id = r.rol_id
        WHERE u.id=? AND p.id=?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $usuario_id, $personal_id);
$stmt->execute();
$result = $stmt->get_result();

$mapaDias = [
    "LUNES"      => 1,
    "MARTES"     => 2,
    "MIERCOLES"  => 3,
    "MIÉRCOLES"  => 3, // por si la BD usa tilde
    "JUEVES"     => 4,
    "VIERNES"    => 5,
    "SABADO"     => 6,
    "SÁBADO"     => 6,
    "DOMINGO"    => 7
];


$q = $conexion->prepare("SELECT dia, hora_inicio, hora_fin FROM activacion WHERE usuario_id = ?");
$q->bind_param("i", $usuario_id);
$q->execute();
$resH = $q->get_result();

while ($h = $resH->fetch_assoc()) {

    $nombreDia = strtoupper(trim($h["dia"])); // Ej: "LUNES"

    if (isset($mapaDias[$nombreDia])) {

        $dia = $mapaDias[$nombreDia];

        $horarios[$dia] = [
            "inicio" => $h["hora_inicio"],
            "fin"    => $h["hora_fin"]
        ];
    }
}



if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'usuario' => [
            'id' => $row['usuario_id'],
            'usuario' => $row['usuario'],
            'estado' => $row['estado']
        ],
        'personal' => [
            'id' => $row['personal_id'],
            'nombre' => $row['nombre'],
            'apellido_paterno' => $row['apellido_paterno'],
            'apellido_materno' => $row['apellido_materno'],
            'correo' => $row['correo'],
            'telefono' => $row['telefono'],
            'celular' => $row['celular'],
            'cargo' => $row['cargo'],
            'rol' => $row['rol_id']
        ],
        'horarios' => $horarios
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
}
