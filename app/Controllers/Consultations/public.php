<?php

setlocale(LC_TIME, 'es_ES.UTF-8');

\FMGlobal\Security\Session::start();

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

    $selectedMode=\FMGlobal\Support\Input::text($_POST,'modo',16,true);
    if (!in_array($selectedMode,['netflix','disney'],true)) throw new \FMGlobal\Http\HttpException(422,'Modo no válido.');
    $_SESSION['modo']=$selectedMode;
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

// Same public availability rule as the dashboard, independent of visitor session.
$publicSchedule=(new \FMGlobal\Repositories\PublicScheduleRepository($conexion))->load();
$fuera_de_horario=!\FMGlobal\Services\Schedules\WeeklySchedule::active($publicSchedule['week']);
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

    $correo = \FMGlobal\Support\Input::text($_POST, 'correo', 254);

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

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

            try { $data=(new \FMGlobal\Services\Http\MailApiClient())->get($url); }
            catch (\FMGlobal\Http\HttpException $e) { \FMGlobal\Support\Logger::exception($e,'mail-api.public'); $error=$e->getMessage(); $data=['data'=>[]]; }

            if (
                !$data
                ||
                empty($data['data'])
            ) {

                $error = $error ?: "No se encontraron códigos.";

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

            try { $data=(new \FMGlobal\Services\Http\MailApiClient())->get($url); }
            catch (\FMGlobal\Http\HttpException $e) { \FMGlobal\Support\Logger::exception($e,'mail-api.public'); $error=$e->getMessage(); $data=['data'=>[]]; }

            if (
                !$data
                ||
                empty($data['data'])
            ) {

                $error = $error ?: "No se encontraron códigos.";

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
