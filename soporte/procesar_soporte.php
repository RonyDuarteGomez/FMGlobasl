<?php

setlocale(LC_TIME, 'es_ES.UTF-8');

session_start();

if (!isset($_SESSION['usuario'])) {

    header("Location: login.php");

    exit();
}

require '../conexion/conexion.php';

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
// CONFIG TEMPORAL
// =====================================================

$password = (require __DIR__ . '/../config/private.php')['support_password'];

$minutes = 30;

// =====================================================
// CONSUMIR API
// =====================================================

$url =
    'https://fmglobals.com/api/buscar_correo.php'
    . '?correo=' . urlencode($correo)
    . '&password=' . urlencode($password)
    . '&minutes=' . $minutes;

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
// INSERT USO SERVICIO
// =====================================================

// tipo fijo soporte/correos
$tipo = 6;

// contar correos encontrados
$num_urls =
    count($lista);

// usuario sesión
$usuario_sesion =
    $_SESSION['usuario'] ?? null;

// insert
$insert = "
    INSERT INTO uso_servicio
    (
        correo,
        num_urls,
        fecha,
        usuario,
        streaming
    )
    VALUES
    (
        ?,
        ?,
        NOW(),
        ?,
        ?
    )
";

$stmt_insert =
    $conexion->prepare($insert);

$stmt_insert->bind_param(
    "sisi",
    $correo,
    $num_urls,
    $usuario_sesion,
    $tipo
);

$stmt_insert->execute();

$stmt_insert->close();

?>

<div class="tarjetasSoporte">

    <?php foreach ($lista as $item): ?>

        <div class="tarjetaSoporte">

            <p>
                <strong>Remitente:</strong>
                <?= htmlspecialchars($item["remitente"]) ?>
            </p>

            <p>
                <strong>Asunto:</strong>
                <?= htmlspecialchars($item["asunto"]) ?>
            </p>

            <p>
                <strong>Fecha:</strong>
                <?= htmlspecialchars($item["fecha"]) ?>
            </p>

            <div class="correo-cuerpo">
                <?= nl2br(htmlspecialchars($item["cuerpo"])) ?>
            </div>

        </div>

    <?php endforeach; ?>

</div>