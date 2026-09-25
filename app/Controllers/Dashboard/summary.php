<?php
$permissions=\FMGlobal\Security\Access::permissions();
if (isset($_GET['report'])) {
    $reportKind=$_GET['report'];
    $required=['advisor'=>'reports.internal','support'=>'reports.support','links'=>'reports.links'];
    if (!is_string($reportKind) || !isset($required[$reportKind])) throw new \FMGlobal\Http\HttpException(422,'Reporte no válido.');
    if (empty($permissions[$required[$reportKind]])) throw new \FMGlobal\Http\HttpException(403,'No tienes permiso para consultar esta actividad.');
    if($reportKind==='links'){require FM_ROOT.'/app/Controllers/Reports/links.php';return;}
    require FM_ROOT.'/app/Controllers/Reports/scoped.php';
    return;
}
$dashboard=(new \FMGlobal\Services\Reports\PersonalDashboard(new \FMGlobal\Repositories\ActivityRepository(database())))->build($permissions,(int)$_SESSION['usuario_id'],\FMGlobal\Security\Access::isAdmin());
$linkAccounts=null;
if(!empty($permissions['services.links'])) $linkAccounts=(new \FMGlobal\Repositories\LinkAccountRepository(database(),new \FMGlobal\Services\Links\AccountVault()))->summary((int)$_SESSION['usuario_id']);
$linkConsultations=null;
if((!empty($permissions['services.links']) || !empty($permissions['reports.links']))) $linkConsultations=(new \FMGlobal\Repositories\LinkAccountRepository(database(),new \FMGlobal\Services\Links\AccountVault()))->consultations((int)$_SESSION['usuario_id']);
$spotifySummary=null;
if(!empty($permissions['services.spotify']))$spotifySummary=(new \FMGlobal\Repositories\SpotifyRepository(database(),new \FMGlobal\Services\Links\AccountVault()))->dashboard((int)$_SESSION['usuario_id']);
$externalAvailability=null;$externalPendingBrowsers=0;
$externalRepo=new \FMGlobal\Repositories\ExternalLinkRepository(database());
if(!\FMGlobal\Security\Access::isAdmin()&&!empty($permissions['services.external_links'])){
 $browserToken=$_COOKIE['fm_external_browser']??'';
 if(!is_string($browserToken)||!preg_match('/^[a-f0-9]{64}$/D',$browserToken))$browserToken='';
 $externalAvailability=$externalRepo->status((int)$_SESSION['usuario_id'],$browserToken,session_id(),true);
}
if(\FMGlobal\Security\Access::isAdmin()&&!empty($permissions['external.restrictions']))$externalPendingBrowsers=$externalRepo->pendingBrowserCount((int)$_SESSION['usuario_id']);
$externalSummary=null;
if(!empty($permissions['services.external_links'])||!empty($permissions['reports.external_links']))$externalSummary=(new \FMGlobal\Repositories\ExternalLinkRepository(database()))->report((int)$_SESSION['usuario_id'],['period'=>'month'],!empty($permissions['services.external_links']));
$spotifyNotifications=!empty($permissions['services.spotify'])?(new \FMGlobal\Repositories\SpotifyRepository(database(),new \FMGlobal\Services\Links\AccountVault()))->notifications((int)$_SESSION['usuario_id']):[];
require FM_ROOT.'/resources/views/Dashboard/summary.php';
