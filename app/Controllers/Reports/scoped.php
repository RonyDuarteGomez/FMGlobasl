<?php
use FMGlobal\Security\{Access,ActivityScope};
$permissions=Access::permissions();
$days=isset($_GET['days'])?filter_var($_GET['days'],FILTER_VALIDATE_INT):30;
if (!in_array($days,[0,7,30,60,90],true)) throw new \FMGlobal\Http\HttpException(422,'Período no válido.');
$page=isset($_GET['page'])?\FMGlobal\Support\Input::id($_GET,'page'):1;
if($page>1000000) throw new \FMGlobal\Http\HttpException(422,'Página no válida.');
$allowed=ActivityScope::operations($permissions);
if (!empty($permissions['services.links'])) $allowed[]=7;
$requested=match($reportKind){'public'=>[1,2],'advisor'=>[3,4,5],'support'=>[6],'links'=>[7],default=>[3,4,5,6,7]};
$operations=array_values(array_intersect($allowed,$requested));
$user=ActivityScope::user($permissions,(int)$_SESSION['usuario_id'],Access::isAdmin());
$activity=new \FMGlobal\Repositories\ActivityRepository(database());
if (in_array($reportKind,['public','advisor','support'],true)) {
    $route=$reportKind==='public'?'inicio.php':'dashboard.php';
    $reportTitle=match($reportKind){'public'=>'Consultas clientes','advisor'=>'Consultas asesores','support'=>'Consultas soporte'};
    $chartServices=match($reportKind){
        'public'=>[1=>['Netflix','#e50914'],2=>['Disney+','#1677d2']],
        'advisor'=>[3=>['Netflix · Estoy de viaje','#e50914'],4=>['Disney+ · Acceso único','#1677d2'],5=>['Netflix · Inicio de sesión','#e89123']],
        'support'=>[6=>['Soporte','var(--bs-primary)']]
    };
    $search=\FMGlobal\Support\Input::text($_GET,'q',100);
    $size=isset($_GET['size'])?filter_var($_GET['size'],FILTER_VALIDATE_INT):20;
    if (!in_array($size,[10,20,50],true)) throw new \FMGlobal\Http\HttpException(422,'Cantidad de registros no válida.');
    $result=$activity->searchReport($operations,$user,$days,$search,$page,$size);
    $rows=$result['rows']; $page=$result['page']; $pages=$result['pages'];
    $chartDays=$days;
    $series=$activity->dailyByService($operations,$user,$days,$search);
    $pageUrl=static fn(int $target)=>$route.'?'.http_build_query(array_merge($reportKind==='public'?[]:['report'=>$reportKind],['days'=>$days,'q'=>$search,'size'=>$size,'page'=>$target]));
    require FM_ROOT.'/resources/views/Reports/public.php';
    return;
}
$summary=$activity->summary($operations,$user,$days);
$rows=$activity->details($operations,$user,$days,$page);
$series=$activity->daily($operations,$user,30);
$fromDashboard=in_array($reportKind,['advisor','support','links'],true);
$route=$fromDashboard?'dashboard.php':($reportKind==='public'?'inicio.php':'soporte/reportes.php');
$reportTitle=match($reportKind){'public'=>'Consultas clientes','advisor'=>'Consultas asesores','support'=>'Consultas soporte','links'=>'Links generados',default=>'Consultas internas'};
$pageUrl=static fn(int $target)=>$route.'?'.http_build_query(array_merge($fromDashboard?['report'=>$reportKind]:[],['days'=>$days,'page'=>$target]));
require FM_ROOT.'/resources/views/Reports/scoped.php';
