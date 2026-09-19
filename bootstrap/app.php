<?php
declare(strict_types=1);

if (defined('FM_ROOT')) return;
define('FM_ROOT', dirname(__DIR__));
date_default_timezone_set('America/Lima');

if (is_file(FM_ROOT . '/vendor/autoload.php')) require_once FM_ROOT . '/vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
    $legacy = [
        'CorreoConfig' => 'config/CorreoConfig.php',
        'CorreoService' => 'app/Services/Mail/CorreoService.php',
        'DisneyOtpService' => 'app/Services/Extraction/DisneyOtpService.php',
        'NetflixOtpService' => 'app/Services/Extraction/NetflixOtpService.php',
        'NetflixLinkService' => 'app/Services/Extraction/NetflixLinkService.php',
        'GmailProvider' => 'app/Providers/Mail/GmailProvider.php',
        'ImapProvider' => 'app/Providers/Mail/ImapProvider.php',
        'RegexHelper' => 'app/Support/RegexHelper.php',
        'StreamingRules' => 'app/Support/StreamingRules.php',
    ];
    $file = $legacy[$class] ?? null;
    if (str_starts_with($class, 'FMGlobal\\')) {
        $file = 'app/' . str_replace('\\', '/', substr($class, strlen('FMGlobal\\'))) . '.php';
    }
    if ($file && is_file(FM_ROOT . '/' . $file)) require_once FM_ROOT . '/' . $file;
});

function database(): mysqli
{
    $connection = \FMGlobal\Repositories\Database::connection();
    // Puente para el proveedor heredado. Nuevos repositorios reciben la conexion.
    $GLOBALS['conexion'] = $connection;
    return $connection;
}

function fm_dispatch(string $route): void
{
    $routes = require FM_ROOT . '/routes/web.php';
    if (!isset($routes[$route])) {
        http_response_code(404);
        echo 'Pagina no encontrada.';
        return;
    }
    require FM_ROOT . '/' . $routes[$route];
}
