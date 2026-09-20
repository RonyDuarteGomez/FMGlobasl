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
// CONSUMIR API
// =====================================================

$url =
    'https://fmglobals.com/api/netflix_link.php?correo='
    . urlencode($correo);

$data = (new \FMGlobal\Services\Http\MailApiClient())->get($url);

if (
    !$data
    ||
    empty($data['data'])
) {

    echo "<p>No se encontraron enlaces.</p>";

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

        "nombre" =>
            $item['nombre'],

        "fecha" =>
            strftime(
                "%d de %B de %Y - %H:%M",
                strtotime($item['date'])
            ),

        "url" =>
            $item['link']
    ];
}

// =====================================================
// INSERT USO SERVICIO
// =====================================================

// tipo fijo Netflix Link
$tipo = 3;

// contar URLs encontradas
$num_urls =
    count(
        array_filter(
            array_column(
                $lista,
                'url'
            )
        )
    );

// usuario sesión
$usuario_sesion =
    isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;

// insert
(new \FMGlobal\Repositories\UsageRepository($conexion))->register($correo, $num_urls, $usuario_sesion, $tipo);


require FM_ROOT . '/resources/views/Consultations/netflix-link.php';
