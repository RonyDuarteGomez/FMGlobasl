<?php
namespace FMGlobal\Security;
use FMGlobal\Http\HttpException;
final class Csrf
{
    public static function token(): string { Session::start(); return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32)); }
    public static function field(): string { return '<input type="hidden" name="_csrf" value="'.self::token().'">'; }
    public static function verify(): void
    {
        $actual = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? '';
        if (!is_string($actual) || !hash_equals(self::token(), $actual)) throw new HttpException(419, 'La sesión del formulario venció. Recarga la página.');
    }
}
