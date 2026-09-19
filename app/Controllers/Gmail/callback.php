<?php

ini_set('display_errors', 1);

error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {

    session_start();
}

$conexion = database();

require_once FM_ROOT . '/vendor/autoload.php';

// =====================================================
// GOOGLE CLIENT
// =====================================================

$client =
    new Google_Client();

$client->setAuthConfig( FM_ROOT . '/config/credentials.json' );

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

(new \FMGlobal\Repositories\GmailTokenRepository($conexion))->save($correo, $refreshToken);


// =====================================================
// REDIRECT HOME
// =====================================================

$_SESSION['mensaje_gmail'] =
    'Correo autorizado correctamente';

header(
    'Location: home.php?modulo=autoriza'
);

exit();

