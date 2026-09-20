<?php

header('Content-Type: application/json');


// =====================================================
// PARÁMETROS
// =====================================================

$proveedor = $_GET['proveedor'] ?? (str_ends_with(strtolower(\FMGlobal\Support\Input::email($_GET)), '@gmail.com') ? 'gmail' : 'imap');

$correo = $_GET['correo'] ?? '';

$password = $_GET['password'] ?? '';

$minutes = $_GET['minutes'] ?? 15;

// =====================================================
// VALIDACIONES
// =====================================================

if (empty($correo)) {

    echo json_encode([
        'ok' => false,
        'message' => 'Correo requerido'
    ]);

    exit;
}

// =====================================================
// SERVICE
// =====================================================

$service = new CorreoService();

$resultado = $service->buscarCorreos(
    $proveedor,
    $correo,
    $password,
    $minutes
);

// =====================================================
// RESPONSE
// =====================================================

echo json_encode(
    $resultado,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
);
