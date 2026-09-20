<?php
namespace FMGlobal\Security;
final class Session
{
    public const IDLE_SECONDS = 1800;
    public static function checkIdle(): void
    {
        self::start();
        if (!empty($_SESSION['usuario_id']) && isset($_SESSION['last_activity']) && time()-(int)$_SESSION['last_activity']>=self::IDLE_SECONDS) {
            self::clear();
            throw new \FMGlobal\Http\HttpException(401,'Tu sesión ha vencido. Inicia sesión nuevamente.');
        }
    }
    public static function touch(): void
    {
        if (!empty($_SESSION['usuario_id'])) {
            $_SESSION['last_activity']=time();
            header('X-Session-Remaining: '.self::IDLE_SECONDS);
        }
    }
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        ini_set('session.gc_maxlifetime', (string)self::IDLE_SECONDS);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','httponly'=>true,'samesite'=>'Lax']);
        session_start();
    }
    public static function clear(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', ['expires'=>time()-42000,'path'=>$p['path'],'domain'=>$p['domain'],'secure'=>$p['secure'],'httponly'=>true,'samesite'=>'Lax']);
        }
        session_destroy();
    }
}
