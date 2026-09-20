<?php

header('Content-Type: application/json');



// =====================================================
// PARÁMETROS
// =====================================================

$correo =
    $_GET['correo'] ?? '';

// =====================================================
// VALIDAR CORREO
// =====================================================

if (empty($correo)) {

    echo json_encode([

        'ok' => false,

        'message' => 'Correo requerido'

    ]);

    exit;
}

// =====================================================
// CONFIGURACIÓN
// =====================================================

$config =
    CorreoConfig::getConfig(
        $correo
    );

$proveedor =
    $config['proveedor'];

$password =
    $config['password'];

$minutes =
    $config['minutes'];

// =====================================================
// SERVICE
// =====================================================

$service =
    new NetflixOtpService();

$resultado =
    $service->buscarOtp(
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

    JSON_PRETTY_PRINT
    |
    JSON_UNESCAPED_UNICODE
);
