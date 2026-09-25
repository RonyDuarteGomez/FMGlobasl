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
    ini_set('display_errors', '0');
    $routes = require FM_ROOT.'/routes/web.php';
    $json = (in_array($route,['usuario/usuarios.php','servicios/generador.php','mantenimiento/restricciones-generador.php','reportes/links-externos.php','soporte/link.php','gestion/spotify.php','gestion/clientes.php','reportes/ventas-spotify.php'],true) && (($_SERVER['REQUEST_METHOD']??'GET')==='POST' || isset($_GET['action']))) || str_starts_with($route,'api/') || in_array($route,['session.php','usuario/usuario_get.php','permisos/save.php','activacion/actualizar_activacion.php'],true);
    $level = ob_get_level(); ob_start();
    try {
        if (!isset($routes[$route])) throw new \FMGlobal\Http\HttpException(404,'Página no encontrada.');
        $method=$_SERVER['REQUEST_METHOD']??'GET';
        $postOnly=['session.php','permisos/save.php','logout.php','oauth_gmail.php','usuario/usuario_save.php','usuario/usuario_toggle.php','activacion/actualizar_activacion.php','soporte/procesar_disney.php','soporte/procesar_netflix1.php','soporte/procesar_netflix2.php','soporte/procesar_soporte.php'];
        $allowed=in_array($route,$postOnly,true)?['POST']:(in_array($route,['usuario/usuarios.php','servicios/generador.php','mantenimiento/restricciones-generador.php','login.php','validacion.php','soporte/link.php','gestion/spotify.php','gestion/clientes.php','mantenimiento_gmail.php'],true)?['GET','POST']:['GET']);
        if (!in_array($method,$allowed,true)) { header('Allow: '.implode(', ',$allowed)); throw new \FMGlobal\Http\HttpException(405,'Método no permitido.'); }
        if (!str_starts_with($route,'api/')) \FMGlobal\Security\Session::start();
        \FMGlobal\Security\Access::check($route);
        if (str_starts_with($route,'api/')) \FMGlobal\Support\Input::email($_GET);
        if ($method==='POST') \FMGlobal\Security\Csrf::verify();
        if (!str_starts_with($route,'api/') && !in_array($route,['login.php','validacion.php','logout.php'],true)) \FMGlobal\Security\Session::touch();
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        require FM_ROOT.'/'.$routes[$route];
        ob_end_flush();
    } catch (\Throwable $e) {
        while(ob_get_level()>$level) ob_end_clean();
        $known=$e instanceof \FMGlobal\Http\HttpException;
        $status=$known?$e->status:500;
        if ($known && $status>=500) \FMGlobal\Support\Logger::exception($e,$route);
        $message=$known?$e->getMessage():'No se pudo completar la operación. Referencia: '.\FMGlobal\Support\Logger::exception($e,$route);
        http_response_code($status);
        if($status===401 && $route==='home.php') { header('Location: '.\FMGlobal\Support\CleanUrls::base().'ingresar?expired=1'); return; }
        header('Content-Type: '.($json?'application/json':'text/plain').'; charset=UTF-8');
        echo $json?json_encode(['ok'=>false,'success'=>false,'message'=>$message],JSON_UNESCAPED_UNICODE):$message;
    }
}
