<?php $escape=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); ?>
<section class="personal-dashboard">
<?php $spotifyAttention=!empty($spotifySummary['admin'])&&!empty($spotifySummary['accounts']['fallen']); ?>
<?php if($spotifyAttention || $dashboard['showActivation'] || (!empty($dashboard['global']) && !empty($linkAccounts['orphaned']))): ?>
<div class="dashboard-totals">
<?php if($dashboard['showActivation']): ?>
<div class="activation-status"><button type="button" class="dashboard-status-link" data-open-module="Activacion"><span>Soporte de clientes</span></button><span class="activation-indicator">[<span class="activation-dot <?= $dashboard['activation']===null?'unknown':($dashboard['activation']?'active':'inactive') ?>" aria-hidden="true"></span> <strong><?= $dashboard['activation']===null?'Sin horario configurado':($dashboard['activation']?'Activo':'Inactivo') ?></strong>]</span>
</div>
<?php endif ?>
<?php if(!empty($dashboard['global']) && !empty($linkAccounts['orphaned'])): ?><div class="activation-status"><button type="button" class="dashboard-status-link" data-open-module="Link" title="Hay cuentas Link Netflix asignadas a usuarios sin acceso o inactivos."><span>Link Netflix</span></button><span class="activation-indicator needs-attention" title="Cuentas asignadas a usuarios sin acceso o inactivos. Revisa sus asignaciones en Link Netflix.">[<svg class="link-warning-icon" width="14" height="14" viewBox="0 0 24 24" aria-hidden="true"><path fill="var(--bs-warning)" d="M12 2 1 22h22L12 2Z"/><path stroke="#212529" stroke-width="2" d="M12 8v7m0 2v2"/></svg> <strong>Requiere atención</strong>]</span></div><?php endif ?>
<?php if($spotifyAttention): ?><div class="activation-status"><button type="button" class="dashboard-status-link" data-open-module="Spotify">Spotify</button><span class="activation-indicator needs-attention">[<svg class="link-warning-icon" width="14" height="14" viewBox="0 0 24 24" aria-hidden="true"><path fill="var(--bs-warning)" d="M12 2 1 22h22L12 2Z"/><path stroke="#212529" stroke-width="2" d="M12 8v7m0 2v2"/></svg> <strong>Necesita atención</strong>]</span></div><?php endif ?>
</div>
<?php endif ?>
<div class="dashboard-grid dashboard-overview">
<?php if(!empty($spotifySummary)) require FM_ROOT.'/resources/views/Dashboard/spotify.php'; ?>
<?php if(!empty($linkAccounts)) require FM_ROOT.'/resources/views/Dashboard/link-accounts.php'; ?>
<?php if(!empty($linkConsultations)) require FM_ROOT.'/resources/views/Dashboard/link-consultations.php'; ?>
<?php foreach($dashboard['cards'] as $card): ?>
<article class="dashboard-card <?= isset($card['mix'])?'dashboard-mix-card':'dashboard-activity-card' ?> card card-outline card-primary"><div class="dashboard-card-heading"><h3><?= $escape($card['title']) ?></h3><?php if($card['visible']): ?><span class="metric-period">Últimos 30 días</span><?php endif ?></div>
<?php if($card['visible']): ?>
<?php if(isset($card['mix'])):
$total=$card['mix']['netflix']+$card['mix']['disney'];
$netflix=$total?round(100*$card['mix']['netflix']/$total,1):0;
$disney=$total?round(100-$netflix,1):0;
?>
<div class="platform-mix">
<svg class="platform-donut" viewBox="0 0 120 120" role="img" aria-label="<?= $total?'Netflix '.$netflix.'%, Disney '.$disney.'%':'Sin consultas en los últimos 30 días' ?>">
<circle class="donut-track" cx="60" cy="60" r="46"/>
<?php if($total): ?>
<circle class="donut-disney" cx="60" cy="60" r="46"/>
<circle class="donut-netflix" cx="60" cy="60" r="46" pathLength="100" stroke-dasharray="<?= $netflix ?> 100" transform="rotate(-90 60 60)"/>
<?php endif ?>
<text class="donut-total" x="60" y="59"><?= (int)$total ?></text><text class="donut-caption" x="60" y="76">consultas</text>
</svg>
<ul class="platform-legend">
<li><span class="platform-dot netflix"></span><span>Netflix<strong><?= $netflix ?>% <small>(<?= $card['mix']['netflix'] ?>)</small></strong></span></li>
<li><span class="platform-dot disney"></span><span>Disney<strong><?= $disney ?>% <small>(<?= $card['mix']['disney'] ?>)</small></strong></span></li>
</ul>
</div>

<?php else: ?>
<div class="secondary-metrics"><div class="card-metric"><?= (int)$card['summary']['total'] ?><span><?= $card['links']?'links generados':'consultas' ?></span></div>
</div><figure class="daily-activity <?= $card['links']?'links-activity':'support-activity' ?>"><figcaption>Actividad diaria</figcaption><?= \FMGlobal\Support\DailyBars::render($card['series']) ?></figure>
<?php endif ?>
<?php else: ?><p>Sin permiso para visualizar actividad.</p><?php endif ?>
<?php if($card['visible'] && $card['canReport']): ?><button type="button" class="dashboard-link" data-load-url="<?= $escape($card['reportUrl']) ?>"><?= $escape($card['action']) ?> <span aria-hidden="true">→</span></button><?php endif ?>
</article><?php endforeach ?>
</div>
<?php if(!$dashboard['cards'] && empty($linkAccounts) && empty($linkConsultations) && empty($spotifySummary)): ?><div class="card card-body"><p class="mb-0">No tienes consultas habilitadas en este dashboard.</p></div><?php endif ?>
</section>
