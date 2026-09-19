<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../services/CorreoService.php';

// =====================================================
// PARÁMETROS
// =====================================================

$proveedor = $_GET['proveedor'] ?? 'imap';

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