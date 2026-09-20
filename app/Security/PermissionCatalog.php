<?php
namespace FMGlobal\Security;
final class PermissionCatalog
{
    public const ITEMS = [
        'services.activation'=>['Servicios','Soporte clientes'],
        'services.links'=>['Gestión','Link Netflix'],
        'services.gmail'=>['Servicios','Autorizar Gmail'],
        'services.advisor'=>['Servicios','Asesor'],
        'services.support'=>['Servicios','Soporte'],
        'reports.public'=>['Reportes','Consultas clientes'],
        'reports.internal'=>['Reportes','Consultas asesores'],
        'reports.support'=>['Reportes','Consultas soporte'],
        'users.manage'=>['Mantenimiento','Usuarios'],
        'permissions.manage'=>['Mantenimiento','Permisos'],
        'activity.own'=>['Información','Ver actividad propia'],
        'activity.all'=>['Información','Ver actividad de todos'],
    ];
    public static function defaults(int $role): array
    {
        return match ($role) {
            1=>array_keys(self::ITEMS),
            2=>['services.advisor','activity.own'],
            3=>['services.support','reports.support','activity.own'],
            default=>[],
        };
    }
    public static function route(string $route): ?string
    {
        return match ($route) {
            'home.php','dashboard.php','logout.php','session.php'=>null,
            'inicio.php'=>'reports.public',
            'soporte/reportes.php'=>'reports.internal',
            'usuario/usuarios.php','usuario/usuario_list.php','usuario/usuario_get.php','usuario/usuario_save.php','usuario/usuario_toggle.php'=>'users.manage',
            'permisos/index.php','permisos/save.php'=>'permissions.manage',
            'activacion/activacion.php','activacion/actualizar_activacion.php'=>'services.activation',
            'mantenimiento_gmail.php','oauth_gmail.php','oauth2callback.php'=>'services.gmail',
            'soporte/link.php'=>'services.links',
            'soporte/asesor.php','soporte/procesar_disney.php','soporte/procesar_netflix1.php','soporte/procesar_netflix2.php'=>'services.advisor',
            'soporte/soporte.php','soporte/procesar_soporte.php'=>'services.support',
            default=>throw new \LogicException('Ruta protegida sin política.'),
        };
    }
}
