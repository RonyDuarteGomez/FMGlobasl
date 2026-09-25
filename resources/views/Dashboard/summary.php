<?php
$escape=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
// Reutiliza exclusivamente los resúmenes filtrados por el controlador actual.
$groups=[];
if(!empty($spotifySummary)) {
    if($spotifySummary['admin']) $groups[]=['Spotify · Pagos','fa-credit-card',$spotifySummary['payments'],['current'=>'Al día','soon'=>'Por vencer','expired'=>'Vencidos'],['success','warning','danger'],'Spotify'];
    $groups[]=['Spotify · Renovaciones','fa-calendar-check',$spotifySummary['renewals'],['current'=>'Al día','soon'=>'Por vencer','expired'=>'Vencidas'],['success','warning','danger'],'Spotify'];
    $groups[]=['Spotify · Cuentas','fa-spotify',$spotifySummary['accounts'],['assigned'=>'Asignadas','free'=>'Libres','fallen'=>'Caídas'],['primary','success','danger'],'Spotify'];
}
if(!empty($linkAccounts)) {
    $groups[]=['Link Netflix · Estado','fa-link',['active'=>$linkAccounts['active'],'errors'=>$linkAccounts['errors']],['active'=>'Activas','errors'=>'Con error'],['success','danger'],'Link'];
    if($linkAccounts['admin']) $groups[]=['Link Netflix · Asignación','fa-user-check',['assigned'=>max(0,$linkAccounts['total']-$linkAccounts['unassigned']-$linkAccounts['orphaned']),'orphaned'=>$linkAccounts['orphaned'],'free'=>$linkAccounts['unassigned']],['assigned'=>'Asignadas','orphaned'=>'Sin acceso','free'=>'Sin asignar'],['primary','danger','secondary'],'Link'];
}
$alerts=[];
if(!empty($externalPendingBrowsers))$alerts[]=['Generador de Link',$externalPendingBrowsers.' solicitudes de navegador pendientes','RestriccionesExterno'];
if(!empty($spotifySummary['admin'])) {
    if($spotifySummary['accounts']['fallen']) $alerts[]=['Spotify',$spotifySummary['accounts']['fallen'].' cuentas caídas','Spotify'];
    if($spotifySummary['payments']['expired']) $alerts[]=['Spotify',$spotifySummary['payments']['expired'].' pagos vencidos','Spotify'];
}
if(!empty($dashboard['global'])&&!empty($linkAccounts['orphaned'])) $alerts[]=['Link Netflix',$linkAccounts['orphaned'].' cuentas asignadas sin acceso','Link'];
$activities=[];
if(!empty($linkConsultations)) $activities[]=['Consultas link',(int)$linkConsultations['generated'],$linkConsultations['generationSeries'],null,!empty($permissions['reports.links'])?'reporteLinks':null,null];
if(!empty($externalSummary)) {
    $daily=array_column($externalSummary['daily'],'total','day');$series=[];
    for($day=new DateTimeImmutable($externalSummary['from']);$day<=new DateTimeImmutable($externalSummary['to']);$day=$day->modify('+1 day')) $series[$day->format('Y-m-d')]=(int)($daily[$day->format('Y-m-d')]??0);
    $activities[]=['Generador de Link',(int)$externalSummary['total'],$series,null,!empty($permissions['reports.external_links'])?'ReporteExterno':null,null];
}
foreach($dashboard['cards'] as $card) {
    if(!$card['visible']) continue;
    $mix=$card['breakdown']??$card['mix']??null;
    $activities[]=[$card['title'],$mix?array_sum($mix):(int)$card['summary']['total'],$card['series']??null,$mix,null,$card['canReport']?$card['reportUrl']:null];
}
?>
<section class="fm-dashboard-proposal">
<header class="proposal-heading"><div><h2>Resumen operativo</h2><p><?= $dashboard['global']?'Vista general del equipo':'Tu actividad y cuentas asignadas' ?></p></div></header>
<?php if($alerts || $dashboard['showActivation']): ?><div class="proposal-alerts" aria-label="Estado de los servicios">
<?php if($dashboard['showActivation']): ?><button type="button" class="proposal-status" data-open-module="Activacion"><span class="proposal-status-dot <?= $dashboard['activation']===null?'bg-secondary':($dashboard['activation']?'bg-success':'bg-danger') ?>"></span><span>Soporte de clientes <strong><?= $dashboard['activation']===null?'Sin horario':($dashboard['activation']?'Activo':'Inactivo') ?></strong></span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button><?php endif ?>
<?php foreach($alerts as [$label,$message,$module]): ?><button type="button" class="proposal-status proposal-attention" data-open-module="<?= $escape($module) ?>"><i class="fa-solid fa-triangle-exclamation text-warning" aria-hidden="true"></i><span><?= $escape($label) ?><strong><?= $escape($message) ?></strong></span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button><?php endforeach ?>
</div><?php endif ?>
<?php if(!empty($externalAvailability))require FM_ROOT.'/resources/views/Dashboard/external-availability.php'; ?>
<?php if($spotifyNotifications): $noticeCount=count($spotifyNotifications); ?>
<details class="dashboard-notifications">
 <summary><i class="fa-regular fa-bell" aria-hidden="true"></i><span><strong>Spotify</strong> · <?= $noticeCount ?> <?= $noticeCount===1?'aviso de cuenta restablecida':'avisos de cuentas restablecidas' ?></span><span class="notification-toggle"><span class="notification-show">Ver avisos</span><span class="notification-hide">Ocultar avisos</span><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></span></summary>
 <div class="notification-list" tabindex="0" role="region" aria-label="Avisos pendientes de Spotify"><ul>
 <?php foreach($spotifyNotifications as $notification): ?><li><?= $escape($notification['message']) ?></li><?php endforeach ?>
 </ul></div>
 <div class="notification-footer"><span>Los avisos permanecen pendientes hasta marcarlos como leídos.</span><button type="button" class="btn btn-sm btn-outline-primary" data-open-module="Spotify">Gestionar avisos en Spotify</button></div>
</details>
<?php endif ?>
<?php if($groups): ?><div class="proposal-section-heading"><h3>Cuentas y vencimientos</h3><span>Estado actual</span></div><div class="proposal-inventory">
<?php foreach($groups as [$title,$icon,$values,$labels,$colors,$module]): $total=array_sum(array_intersect_key($values,$labels)); ?>
<article class="card proposal-tile"><header><h4><?= $escape($title) ?></h4><i class="<?= $icon==='fa-spotify'?'fa-brands':'fa-solid' ?> <?= $icon ?>" aria-hidden="true"></i></header><div class="proposal-total"><strong><?= (int)$total ?></strong><span>en total</span></div>
<div class="proposal-segmented" role="img" aria-label="<?= $escape(implode(', ',array_map(fn($key)=>$labels[$key].': '.(int)$values[$key],array_keys($labels)))) ?>"><?php foreach(array_keys($labels) as $i=>$key): if($total && $values[$key]): ?><span class="bg-<?= $colors[$i] ?>" style="width:<?= 100*$values[$key]/$total ?>%"></span><?php endif; endforeach ?></div>
<ul class="proposal-breakdown"><?php foreach(array_keys($labels) as $i=>$key): ?><li><span><i class="proposal-status-dot bg-<?= $colors[$i] ?>" aria-hidden="true"></i><?= $escape($labels[$key]) ?></span><strong><?= (int)$values[$key] ?></strong><small><?= $total?round(100*$values[$key]/$total):0 ?>%</small></li><?php endforeach ?></ul><button type="button" class="proposal-action" data-open-module="<?= $module ?>">Ir a <?= str_starts_with($title,'Spotify')?'Spotify':'Link Netflix' ?> <span aria-hidden="true">→</span></button></article>
<?php endforeach ?></div><?php endif ?>
<?php if($activities): ?><div class="proposal-section-heading"><h3>Consultas</h3><span>Últimos 30 días</span></div><div class="proposal-activity-grid">
<?php foreach($activities as [$title,$total,$series,$mix,$module,$url]): ?><article class="card proposal-tile proposal-activity"><header><h4><?= $escape($title) ?></h4><i class="fa-solid fa-chart-simple" aria-hidden="true"></i></header><div class="proposal-total"><strong><?= $total ?></strong><span>consultas</span></div>
<?php if($mix!==null): ?><div class="proposal-platforms"><?php foreach((isset($mix['netflix_access'])?['netflix_access'=>'Netflix · Código de acceso','netflix_login'=>'Netflix · Código de inicio','disney'=>'Disney']:['netflix'=>'Netflix','disney'=>'Disney']) as $key=>$label): $count=(int)$mix[$key]; ?><div><div class="proposal-platform-label"><span><?= $label ?></span><strong><?= $count ?></strong><small><?= $total?round(100*$count/$total):0 ?>%</small></div><div class="proposal-platform-track"><span class="proposal-<?= $key ?>" style="width:<?= $total?100*$count/$total:0 ?>%"></span></div></div><?php endforeach ?></div><?php if(!$total): ?><small class="text-body-secondary">Sin actividad en este período</small><?php endif ?>
<?php else: ?><figure class="proposal-chart"><?= \FMGlobal\Support\DailyBars::render($series??[]) ?></figure><?php endif ?>
<?php if($module || $url): ?><button type="button" class="proposal-action" <?= $module?'data-open-module="'.$escape($module).'"':'data-load-url="'.$escape($url).'"' ?>>Ver reporte detallado <span aria-hidden="true">→</span></button><?php endif ?></article><?php endforeach ?></div><?php endif ?>
<?php if(!$groups&&!$activities): ?><div class="card card-body">No tienes actividad habilitada en este dashboard.</div><?php endif ?>
</section>
