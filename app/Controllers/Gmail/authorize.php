<?php

\FMGlobal\Security\Session::start();

require_once FM_ROOT . '/vendor/autoload.php';

// =====================================================
// VALIDAR LOGIN
// =====================================================

if (!isset($_SESSION['usuario'])) {

    header("Location: login.php");

    exit();
}

// =====================================================
// GOOGLE CLIENT
// =====================================================

$client =
    new Google_Client();

$client->setClientId(
    '720018214546-6e33ogbh2bmelisj4104q2ijn58u8cho.apps.googleusercontent.com'
);

$client->setClientSecret(
    (require FM_ROOT . '/config/private.php')['google_client_secret']
);

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
// REDIRECT GOOGLE
// =====================================================

$email=null;
if (!empty($_POST['correo'])) {
    $email=\FMGlobal\Support\Input::email($_POST);
    $client->setLoginHint($email);
}
$client->setState(\FMGlobal\Security\OAuthState::create($email));

$authUrl =
    $client->createAuthUrl();

header(
    'Location: ' . $authUrl
);

exit();
