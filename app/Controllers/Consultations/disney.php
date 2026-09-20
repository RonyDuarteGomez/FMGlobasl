<?php

setlocale(LC_TIME, 'es_ES.UTF-8');

\FMGlobal\Security\Session::start();

$conexion = database();

date_default_timezone_set('America/Lima');

header("Content-Type: text/html; charset=UTF-8");

// =====================================================
// VALIDAR SESIÓN
// =====================================================

if (!isset($_SESSION['usuario'])) {

    header("Location: login.php");

    exit();
}

// =====================================================
// OBTENER CORREO
// =====================================================

$correo = \FMGlobal\Support\Input::email($_POST);

if ($correo === "") {

    echo "<p>Error: No se recibió correo.</p>";

    exit;
}

// =====================================================
// CONSUMIR API
// =====================================================

$url =
    'https://fmglobals.com/api/disney_otp.php?correo='
    . urlencode($correo);

$data = (new \FMGlobal\Services\Http\MailApiClient())->get($url);

if (
    !$data
    ||
    empty($data['data'])
) {

    echo "<p>No se encontraron códigos.</p>";

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

        "codigo" =>
            $item['codigo'],

        "fecha" =>
            strftime(
                "%d de %B de %Y - %H:%M",
                strtotime($item['date'])
            )
    ];
}

// =====================================================
// INSERT USO SERVICIO
// =====================================================

// tipo fijo Disney
$tipo = 4;

// contar códigos encontrados
$num_urls =
    count(
        array_filter(
            array_column(
                $lista,
                'codigo'
            )
        )
    );

// usuario sesión
$usuario_sesion =
    $_SESSION['usuario'] ?? null;

// insert
(new \FMGlobal\Repositories\UsageRepository($conexion))->register($correo, $num_urls, $usuario_sesion, $tipo);


require FM_ROOT . '/resources/views/Consultations/disney.php';
