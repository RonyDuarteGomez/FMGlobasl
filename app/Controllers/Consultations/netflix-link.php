<?php

setlocale(LC_TIME, 'es_ES.UTF-8');

session_start();

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

$correo =
    trim(
        $_POST["correo"] ?? ""
    );

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

$response =
    @file_get_contents($url);

// =====================================================
// VALIDAR RESPONSE API
// =====================================================

if ($response === false) {

    echo "<p>Error: No se pudo consumir la API.</p>";

    exit;
}

$data =
    json_decode(
        $response,
        true
    );

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
    $_SESSION['usuario'] ?? null;

// insert
(new \FMGlobal\Repositories\UsageRepository($conexion))->register($correo, $num_urls, $usuario_sesion, $tipo);


require FM_ROOT . '/resources/views/Consultations/netflix-link.php';
