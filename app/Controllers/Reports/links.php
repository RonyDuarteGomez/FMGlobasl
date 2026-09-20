<?php
use FMGlobal\Support\Input;
$days=isset($_GET['days'])?filter_var($_GET['days'],FILTER_VALIDATE_INT):30;
$size=isset($_GET['size'])?filter_var($_GET['size'],FILTER_VALIDATE_INT):20;
if(!in_array($days,[0,7,30,60,90],true)||!in_array($size,[10,20,50],true))throw new \FMGlobal\Http\HttpException(422,'Filtro no válido.');
$page=isset($_GET['page'])?Input::id($_GET,'page'):1;$search=Input::text($_GET,'q',100);
$actor=\FMGlobal\Security\Access::isAdmin()?null:(int)$_SESSION['usuario_id'];
$result=(new \FMGlobal\Repositories\LinkReportRepository(database(),new \FMGlobal\Services\Links\AccountVault()))->report($actor,$days,$search,$page,$size);
$rows=$result['rows'];$series=$result['series'];$page=$result['page'];$pages=$result['pages'];$chartDays=$days;
$chartServices=[1=>['Intentos de generación','var(--bs-primary)']];
$reportTitle='Consultas link';$route='dashboard.php';$reportKind='links';
$pageUrl=static fn(int $target)=>$route.'?'.http_build_query(['report'=>'links','days'=>$days,'q'=>$search,'size'=>$size,'page'=>$target]);
require FM_ROOT.'/resources/views/Reports/public.php';
