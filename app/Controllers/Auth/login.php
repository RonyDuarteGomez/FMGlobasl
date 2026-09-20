<?php
use FMGlobal\Security\Csrf;
use FMGlobal\Support\Input;
header('X-Frame-Options: DENY');
header("Content-Security-Policy: frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
header('Referrer-Policy: no-referrer');
$mensaje=isset($_GET['expired'])?'Tu sesión ha vencido. Inicia sesión nuevamente.':'';
$username='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        $username=Input::text($_POST,'usuario',50,true,false);
        $password=Input::text($_POST,'clave',72,true,false);
        $config=require FM_ROOT.'/config/database.php';
        $limiter=new \FMGlobal\Security\LoginThrottle(FM_ROOT.'/storage/security/login-'.hash('sha256',$config['database']).'.json');
        $limiter->attempt($username,$_SERVER['REMOTE_ADDR']??'unknown');
        if (preg_match('/[\s\p{Z}]/u',$username) || strlen($password)>72) throw new \FMGlobal\Http\HttpException(422,'Revisa el usuario y la contraseña. El usuario no debe contener espacios.');
        $user=(new \FMGlobal\Services\Auth\LoginService(new \FMGlobal\Repositories\UserRepository(database())))->authenticate($username,$password);
        if (!$user) $mensaje='Usuario o contraseña incorrectos, o cuenta no disponible.';
        else {
            session_regenerate_id(true);
            $_SESSION=['usuario_id'=>(int)$user['id'],'usuario'=>$user['usuario'],'rol_id'=>(int)$user['rol_id'],'nombre_completo'=>trim($user['nombre'].' '.$user['apellido_paterno'].' '.$user['apellido_materno'])];
            \FMGlobal\Security\Session::touch();
            Csrf::token();
            header('Location: sistema/inicio'); exit;
        }
    } catch (\FMGlobal\Http\HttpException $e) {
        http_response_code($e->status);
        if($e->status===429) header('Retry-After: 900');
        $mensaje=$e->getMessage();
    }
}
require FM_ROOT.'/resources/views/Auth/login.php';