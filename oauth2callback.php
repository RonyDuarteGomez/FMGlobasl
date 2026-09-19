
<?php

ini_set('display_errors', 1);

error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {

    session_start();
}

require 'conexion/conexion.php';

require 'vendor/autoload.php';

// =====================================================
// GOOGLE CLIENT
// =====================================================

$client =
    new Google_Client();

$client->setAuthConfig( __DIR__ . '/config/credentials.json' );

$client->setRedirectUri(
    'https://fmglobals.com/oauth2callback.php'
);

$client->addScope(
    Google_Service_Gmail::GMAIL_READONLY
);

$client->setAccessType(
    'offline'
);

$client->setPrompt(
    'consent'
);

// =====================================================
// VALIDAR CODE
// =====================================================

if (!isset($_GET['code'])) {

    exit('No se recibi¨® el code');
}

// =====================================================
// TOKEN
// =====================================================

$token =
    $client->fetchAccessTokenWithAuthCode(
        $_GET['code']
    );

if (isset($token['error'])) {

    echo '<pre>';

    print_r($token);

    echo '</pre>';

    exit;
}

// =====================================================
// ACCESS TOKEN
// =====================================================

$client->setAccessToken(
    $token
);

// =====================================================
// GMAIL PROFILE
// =====================================================

$gmail =
    new Google_Service_Gmail(
        $client
    );

$profile =
    $gmail->users->getProfile(
        'me'
    );

$correo =
    $profile->getEmailAddress();

// =====================================================
// VALIDAR REFRESH TOKEN
// =====================================================

if (
    !isset($token['refresh_token'])
) {

    exit(
        'No se recibi¨® refresh token.
        Revoca permisos y vuelve a autorizar.'
    );
}

$refreshToken =
    $token['refresh_token'];

// =====================================================
// VALIDAR EXISTE
// =====================================================

$sqlExiste = "
    SELECT id
    FROM gmail_tokens
    WHERE correo = ?
    LIMIT 1
";

$stmtExiste =
    $conexion->prepare(
        $sqlExiste
    );

$stmtExiste->bind_param(
    "s",
    $correo
);

$stmtExiste->execute();

$resultadoExiste =
    $stmtExiste->get_result();

// =====================================================
// UPDATE
// =====================================================

if (
    $resultadoExiste->num_rows > 0
) {

    $sqlUpdate = "
        UPDATE gmail_tokens
        SET refresh_token = ?
        WHERE correo = ?
    ";

    $stmtUpdate =
        $conexion->prepare(
            $sqlUpdate
        );

    $stmtUpdate->bind_param(
        "ss",
        $refreshToken,
        $correo
    );

    $stmtUpdate->execute();

    $stmtUpdate->close();

} else {

    // =================================================
    // INSERT
    // =================================================

    $sqlInsert = "
        INSERT INTO gmail_tokens
        (
            correo,
            refresh_token
        )
        VALUES
        (
            ?,
            ?
        )
    ";

    $stmtInsert =
        $conexion->prepare(
            $sqlInsert
        );

    $stmtInsert->bind_param(
        "ss",
        $correo,
        $refreshToken
    );

    $stmtInsert->execute();

    $stmtInsert->close();
}

$stmtExiste->close();

// =====================================================
// REDIRECT HOME
// =====================================================

$_SESSION['mensaje_gmail'] =
    'Correo autorizado correctamente';

header(
    'Location: home.php?modulo=autoriza'
);

exit();

