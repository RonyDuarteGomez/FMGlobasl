<?php

setlocale(LC_TIME, 'es_ES.UTF-8');

session_start();

$conexion = database();

date_default_timezone_set('America/Lima');

// =====================================================
// BOTÓN REGRESAR
// =====================================================

if (isset($_POST['volver'])) {

    unset($_SESSION['modo']);

    header("Location: validacion.php");

    exit;
}

// =====================================================
// CAPTURAR MODO
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['modo'])
) {

    $_SESSION['modo'] =
        $_POST['modo'];
}

$modo =
    $_SESSION['modo'] ?? null;

// =====================================================
// TÍTULOS
// =====================================================

$titulo_formulario = "";

if ($modo === 'netflix') {

    $titulo_formulario =
        "Valida código de Acceso Netflix";
}

if ($modo === 'disney') {

    $titulo_formulario =
        "Valida código de Acceso Disney";
}

// =====================================================
// VALIDACIÓN HORARIO
// =====================================================

$fuera_de_horario = false;

if (!isset($_SESSION['usuario'])) {

    $dia_actual =
        ucfirst(
            strftime("%A")
        );

    $query = "
        SELECT
            hora_inicio,
            hora_fin
        FROM activacion
        WHERE dia = ?
        LIMIT 1
    ";

    $stmt =
        $conexion->prepare($query);

    $stmt->bind_param(
        "s",
        $dia_actual
    );

    $stmt->execute();

    $result =
        $stmt->get_result();

    if (
        $result
        &&
        $data = $result->fetch_assoc()
    ) {

        $hora_inicio =
            (int)substr(
                $data['hora_inicio'],
                0,
                2
            );

        $hora_fin =
            (int)substr(
                $data['hora_fin'],
                0,
                2
            );

        $hora_actual =
            (int)date("H");

        $fuera_de_horario =
            (
                $hora_actual >= $hora_inicio
                &&
                $hora_actual < $hora_fin
            );
    }
}

// =====================================================
// VARIABLES
// =====================================================

$lista_correos = [];

$error = "";

$correo = "";

// =====================================================
// PROCESAR FORMULARIO
// =====================================================

if (
    $modo
    &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    !isset($_POST['modo'])
    &&
    !isset($_POST['volver'])
    &&
    !$fuera_de_horario
) {

    $correo =
        trim(
            $_POST['correo'] ?? ''
        );

    if ($correo === "") {

        $error =
            "Correo requerido.";

    } else {

        // =====================================================
        // NETFLIX
        // =====================================================

        if ($modo === "netflix") {

            $tipo = 1;

            $url =
                'https://fmglobals.com/api/netflix_link.php?correo='
                . urlencode($correo);

            $response =
                @file_get_contents($url);

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

                $error =
                    "No se encontraron códigos.";

            } else {

                foreach (
                    $data['data']
                    as $item
                ) {

                    $lista_correos[] = [

                        'nombre' =>
                            $item['nombre'] ?? '',

                        'fecha' =>
                            strftime(
                                "%d de %B de %Y, %H:%M",
                                strtotime($item['date'])
                            ),

                        'url' =>
                            $item['link'] ?? null,

                        'codigo' =>
                            null
                    ];
                }
            }
        }

        // =====================================================
        // DISNEY
        // =====================================================

        if ($modo === "disney") {

            $tipo = 2;

            $url =
                'https://fmglobals.com/api/disney_otp.php?correo='
                . urlencode($correo);

            $response =
                @file_get_contents($url);

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

                $error =
                    "No se encontraron códigos.";

            } else {

                foreach (
                    $data['data']
                    as $item
                ) {

                    $lista_correos[] = [

                        'nombre' =>
                            'Disney+',

                        'fecha' =>
                            strftime(
                                "%d de %B de %Y, %H:%M",
                                strtotime($item['date'])
                            ),

                        'codigo' =>
                            $item['codigo'] ?? null,

                        'url' =>
                            null
                    ];
                }
            }
        }

        // =====================================================
        // INSERT
        // =====================================================

        if (!empty($lista_correos)) {

            if ($tipo == 1) {

                $num_urls =
                    count(
                        array_filter(
                            array_column(
                                $lista_correos,
                                'url'
                            )
                        )
                    );

            } else {

                $num_urls =
                    count(
                        array_filter(
                            array_column(
                                $lista_correos,
                                'codigo'
                            )
                        )
                    );
            }

            $usuario_sesion =
                $_SESSION['usuario'] ?? null;

            (new \FMGlobal\Repositories\UsageRepository($conexion))->register($correo, $num_urls, $usuario_sesion, $tipo);
        }
    }
}


require FM_ROOT . '/resources/views/Consultations/public.php';
