<?php
namespace FMGlobal\Support;
final class Navigation
{
    public const GROUPS=[
        'Gestión'=>[
            ['Link','Link Netflix','soporte/link.php','services.links','iniciarLink'],
        ],
        'Servicios'=>[
            ['Activacion','Soporte clientes','activacion/activacion.php','services.activation','iniciarActivacion'],
            ['Autoriza','Autorizar Gmail','mantenimiento_gmail.php','services.gmail',''],
            ['asesorMenu','Asesor','soporte/asesor.php','services.advisor','iniciarAsesor'],
            ['soporteMenu','Soporte','soporte/soporte.php','services.support','iniciarSoporte'],
        ],
        'Mantenimiento'=>[
            ['menuUsuarios','Usuarios','usuario/usuarios.php','users.manage','iniciarUsuarios'],
            ['menuPermisos','Permisos','permisos/index.php','permissions.manage',''],
        ],
        'Reportes'=>[
            ['reportePublico','Consultas clientes','inicio.php','reports.public',''],
            ['reportesMenu','Consultas asesores','dashboard.php?report=advisor','reports.internal',''],
            ['reporteSoporte','Consultas soporte','dashboard.php?report=support','reports.support',''],
            ['reporteLinks','Consultas link','dashboard.php?report=links','reports.links',''],
        ],
    ];
    public static function label(string $id): string
    {
        foreach(self::GROUPS as $items) foreach($items as $item) if($item[0]===$id) return $item[1];
        throw new \InvalidArgumentException('Opción de menú no encontrada: '.$id);
    }
    public static function groups(array $permissions): array
    {
        $groups=[];
        foreach(self::GROUPS as $title=>$items) {
            foreach($items as $item) if (!empty($permissions[$item[3]])) $groups[$title][]=$item;
        }
        return $groups;
    }
}
