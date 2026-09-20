<?php

setlocale(LC_TIME, 'es_ES.UTF-8');

\FMGlobal\Security\Session::start();

if (!isset($_SESSION['usuario'])) {

    header("Location: login.php");

    exit();
}

$conexion = database();

date_default_timezone_set('America/Lima');

header("Content-Type: text/html; charset=UTF-8");

// =====================================================
// OBTENER CORREO
// =====================================================

$correo = \FMGlobal\Support\Input::email($_POST);

if ($correo === "") {

    echo "<p>Error: No se recibió correo.</p>";

    exit;
}

// =====================================================
// CONFIG TEMPORAL
// =====================================================

$password = (require FM_ROOT . '/config/private.php')['support_password'];

$minutes = 30;

// =====================================================
// CONSUMIR API
// =====================================================

$url =
    'https://fmglobals.com/api/buscar_correo.php'
    . '?correo=' . urlencode($correo)
    . '&password=' . urlencode($password)
    . '&minutes=' . $minutes;

$data = (new \FMGlobal\Services\Mail\SupportConsultation(
    new \FMGlobal\Services\Http\MailApiClient(),
    new \FMGlobal\Repositories\UsageRepository($conexion)
))->search($url,$correo,(int)$_SESSION['usuario_id']);

if (
    !$data
    ||
    empty($data['data'])
) {

    echo "<p>No se encontraron correos.</p>";

    exit;
}

// =====================================================
// ARMAR LISTA
// =====================================================

$lista = [];

foreach (
    $data['data']
    as $item
) {

    $lista[] = [

        "remitente" =>
            $item['from'] ?? 'Desconocido',

        "asunto" =>
            $item['subject'] ?? 'Sin asunto',

        "fecha" =>
            strftime(
                "%d de %B de %Y - %H:%M",
                strtotime($item['date'])
            ),

        "cuerpo" =>
            strip_tags(
                $item['body'] ?? ''
            )
    ];
}

// =====================================================
require FM_ROOT . '/resources/views/Consultations/messages.php';
