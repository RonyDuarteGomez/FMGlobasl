<?php

setlocale(LC_TIME, 'es_ES.UTF-8');

session_start();

require '../conexion/conexion.php';

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
    'https://fmglobals.com/api/disney_otp.php?correo='
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

<div class="tarjetas">

<?php foreach ($lista as $item): ?>

    <div class="tarjeta">

        <p>
            <strong>Fecha:</strong>
            <?= htmlspecialchars($item["fecha"]) ?>
        </p>

        <?php if (!empty($item["codigo"])): ?>

            <div
                class="codigo-disney"
                style="
                    color:#000;
                    padding:10px 15px;
                    border-radius:8px;
                    font-size:1.4rem;
                    font-weight:bold;
                    text-align:center;
                    width:100%;
                    letter-spacing:2px;
                "
            >
                <?= htmlspecialchars($item["codigo"]) ?>
            </div>

        <?php else: ?>

            <div class="aviso">
                Código no encontrado.
            </div>

        <?php endif; ?>

        <div class="aviso">
            * Código válido por 15 minutos.
        </div>

    </div>

<?php endforeach; ?>

</div>