<?php
namespace FMGlobal\Security;
use FMGlobal\Http\HttpException;
final class OAuthState
{
    public static function create(?string $email=null): string
    {
        Session::start();
        $_SESSION['gmail_oauth_state']=['value'=>bin2hex(random_bytes(32)),'expires'=>time()+600,'email'=>$email];
        return $_SESSION['gmail_oauth_state']['value'];
    }
    public static function consume(mixed $state): ?string
    {
        Session::start(); $pending=$_SESSION['gmail_oauth_state']??null; unset($_SESSION['gmail_oauth_state']);
        if (!is_array($pending) || $pending['expires']<time() || !is_string($state) || !hash_equals($pending['value'],$state)) throw new HttpException(400,'La autorización venció o no pertenece a esta sesión. Iníciala nuevamente desde el servidor configurado para Gmail.');
        return $pending['email']??null;
    }
}
