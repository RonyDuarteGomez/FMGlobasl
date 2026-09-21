<?php
$total=(int)$linkAccounts['total'];
$activePercent=$total?round(100*$linkAccounts['active']/$total,1):0;

?>
<article class="dashboard-card card card-outline card-primary account-dashboard-card">
<div class="dashboard-card-heading"><h3>Link Netflix</h3></div>
<div class="account-charts-row">
<div class="account-chart account-state">
<svg class="platform-donut" viewBox="0 0 120 120" role="img" aria-label="<?= $linkAccounts['active'] ?> activas y <?= $linkAccounts['errors'] ?> con error">
<circle class="donut-track" cx="60" cy="60" r="46"/>
<?php if($total): ?><circle class="account-donut-secondary" cx="60" cy="60" r="46"/><circle class="account-donut-primary" cx="60" cy="60" r="46" pathLength="100" stroke-dasharray="<?= $activePercent ?> 100" transform="rotate(-90 60 60)"/><?php endif ?>
<text class="donut-total" x="60" y="59"><?= $total ?></text><text class="donut-caption" x="60" y="76">cuentas</text></svg>
<div class="account-chart-summary">
<div class="account-legend-row"><span class="account-dot account-dot-primary"></span><span>Activas</span><strong><?= $activePercent ?>% <small>(<?= $linkAccounts['active'] ?>)</small></strong></div>
<div class="account-legend-row"><span class="account-dot account-dot-secondary"></span><span>Error</span><strong><?= $total?round(100-$activePercent,1):0 ?>% <small>(<?= $linkAccounts['errors'] ?>)</small></strong></div>
</div></div>
<?php if($linkAccounts['admin']):
$offset=0;
$groups=[
 ['Asignadas',max(0,$total-(int)$linkAccounts['unassigned']-(int)$linkAccounts['orphaned']),'--color-primario'],
 ['Asignadas con problemas',(int)$linkAccounts['orphaned'],'--bs-danger'],
 ['Sin asignar',(int)$linkAccounts['unassigned'],'--bs-secondary'],
]; ?>
<div class="account-chart account-assignment">
<svg class="platform-donut" viewBox="0 0 120 120" role="img" aria-label="Distribución de cuentas asignadas, asignadas con problemas y sin asignar">
<title>Distribución de asignaciones</title><circle class="donut-track" cx="60" cy="60" r="46"/>
<?php foreach($groups as [$label,$count,$color]): $share=$total?100*$count/$total:0;if($share): ?>
<circle cx="60" cy="60" r="46" pathLength="100" stroke="var(<?= $color ?>)" stroke-dasharray="<?= $share ?> <?= 100-$share ?>" stroke-dashoffset="<?= -$offset ?>" transform="rotate(-90 60 60)"><title><?= $label ?>: <?= $count ?></title></circle>
<?php endif;$offset+=$share;endforeach ?>
<text class="donut-total" x="60" y="59"><?= $total ?></text><text class="donut-caption" x="60" y="76">cuentas</text></svg>
<div class="account-chart-summary">
<?php foreach($groups as [$label,$count,$color]): ?><div class="account-legend-row"><span class="account-dot" style="background:var(<?= $color ?>)"></span><span><?= $label ?></span><strong><?= $total?round(100*$count/$total,1):0 ?>% <small>(<?= $count ?>)</small></strong></div><?php endforeach ?>
</div></div><?php endif ?>
</div>
<button type="button" class="dashboard-link" data-open-module="Link">Ir a Link Netflix <span aria-hidden="true">→</span></button>
</article>
