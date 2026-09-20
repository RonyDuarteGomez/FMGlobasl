<?php
$permissions=\FMGlobal\Security\Access::permissions();
if (isset($_GET['report'])) {
    $reportKind=$_GET['report'];
    $required=['advisor'=>'services.advisor','support'=>'reports.support','links'=>'services.links'];
    if (!is_string($reportKind) || !isset($required[$reportKind])) throw new \FMGlobal\Http\HttpException(422,'Reporte no válido.');
    if (empty($permissions[$required[$reportKind]]) || !\FMGlobal\Security\ActivityScope::visible($permissions)) throw new \FMGlobal\Http\HttpException(403,'No tienes permiso para consultar esta actividad.');
    require FM_ROOT.'/app/Controllers/Reports/scoped.php';
    return;
}
$dashboard=(new \FMGlobal\Services\Reports\PersonalDashboard(new \FMGlobal\Repositories\ActivityRepository(database())))->build($permissions,$_SESSION['usuario']);
require FM_ROOT.'/resources/views/Dashboard/summary.php';
