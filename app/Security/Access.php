<?php
namespace FMGlobal\Security;
use FMGlobal\Http\HttpException;
final class Access
{
    private static array $permissions=[];
    public static function can(string $permission): bool { return self::$permissions[$permission]??false; }
    public static function permissions(): array { return self::$permissions; }
    public static function check(string $route): void
    {
        self::$permissions=[];
        if (str_starts_with($route,'api/') || in_array($route,['login.php','validacion.php'],true)) return;
        Session::start();
        Session::checkIdle();
        if (empty($_SESSION['usuario_id'])) throw new HttpException(401,'Inicia sesión para continuar.');
        $user=(new \FMGlobal\Repositories\UserRepository(database()))->identity((int)$_SESSION['usuario_id']);
        if (!$user || (int)$user['estado']!==1) { Session::clear(); throw new HttpException(401,'La sesión ya no está disponible.'); }
        $_SESSION['rol_id']=(int)$user['rol_id'];
        $_SESSION['usuario']=$user['usuario'];
        self::$permissions=(new \FMGlobal\Repositories\PermissionRepository(database()))->effective((int)$user['id']);
        $permission=PermissionCatalog::route($route);
        if ($permission!==null && !self::can($permission)) throw new HttpException(403,'No tienes permiso para esta operación.');
        if (str_starts_with($permission??'','reports.') && !self::can('activity.own') && !self::can('activity.all')) throw new HttpException(403,'No tienes permiso para consultar actividad.');
    }
}
