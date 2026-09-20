<?php
namespace FMGlobal\Support;
final class CleanUrls
{
    public const MODULES = [
        'inicio'=>'inicio', 'Link'=>'link-netflix', 'Activacion'=>'soporte-clientes',
        'Autoriza'=>'autorizar-gmail', 'asesorMenu'=>'asesor', 'soporteMenu'=>'soporte',
        'menuUsuarios'=>'usuarios', 'menuPermisos'=>'permisos',
        'reportePublico'=>'reportes/clientes', 'reportesMenu'=>'reportes/asesores',
        'reporteSoporte'=>'reportes/soporte',
    ];
    public static function base(): string
    {
        $path=parse_url($_SERVER['REQUEST_URI']??'/home.php', PHP_URL_PATH) ?: '/home.php';
        if (preg_match('~^(.*)/sistema(?:/.*)?$~', $path, $match)) return $match[1].'/';
        return rtrim(str_replace('\\','/',dirname($path)), '/').'/';
    }
}