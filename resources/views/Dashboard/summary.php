<?php $escape=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); ?>
<section class="personal-dashboard">
<?php if($dashboard['showActivation']): ?>
<div class="dashboard-totals">
<div class="activation-status"><span>Soporte de clientes</span>
<span class="activation-indicator"><span class="activation-dot <?= $dashboard['activation']===null?'unknown':($dashboard['activation']?'active':'inactive') ?>" aria-hidden="true"></span><strong><?= $dashboard['activation']===null?'Sin horario configurado':($dashboard['activation']?'Activo':'Inactivo') ?></strong></span>
</div>
</div>
<?php endif ?>
<?php foreach([true,false] as $primary):
$cards=array_filter($dashboard['cards'],fn($card)=>$card['primary']===$primary);
if(!$cards) continue;
?>
<div class="dashboard-grid <?= count($cards)===1?'dashboard-single ':'' ?><?= $primary?'dashboard-primary':'dashboard-secondary' ?>">
<?php foreach($cards as $card): ?>
<article class="dashboard-card card card-outline card-primary"><h3><?= $escape($card['title']) ?></h3>
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
<p class="metric-period"><?= $total?'Últimos 30 días':'Sin consultas en los últimos 30 días' ?></p>
<?php else: ?>
<div class="secondary-metrics"><div class="card-metric"><?= (int)$card['summary']['total'] ?><span><?= $card['links']?'links generados':'consultas' ?></span></div>
<p class="metric-period">Últimos 30 días</p></div><figure class="daily-activity <?= $card['links']?'links-activity':'support-activity' ?>"><figcaption>Actividad diaria</figcaption><?= \FMGlobal\Support\DailyBars::render($card['series']) ?></figure>
<?php endif ?>
<?php else: ?><p>Sin permiso para visualizar actividad.</p><?php endif ?>
<?php if($card['visible'] && $card['canReport']): ?><button type="button" class="dashboard-link" data-load-url="<?= $escape($card['reportUrl']) ?>"><?= $escape($card['action']) ?> <span aria-hidden="true">→</span></button><?php endif ?>
</article><?php endforeach ?>
</div>
<?php endforeach ?>
<?php if(!$dashboard['cards']): ?><div class="card card-body"><p class="mb-0">No tienes consultas habilitadas en este dashboard.</p></div><?php endif ?>
</section>