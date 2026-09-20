<?php
$total=(int)$linkAccounts['total'];
$activePercent=$total?round(100*$linkAccounts['active']/$total,1):0;
$palette=['--color-primario','--bs-success','--bs-info','--bs-warning','--color-secundario','--bs-danger'];
?>
<article class="dashboard-card card card-outline card-primary account-dashboard-card">
<div class="dashboard-card-heading"><h3>Cuentas Link Netflix</h3></div>
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
<?php if($linkAccounts['admin']): $offset=0; ?>
<div class="account-chart account-assignment">
<svg class="platform-donut" viewBox="0 0 120 120" role="img" aria-label="Cuentas por usuario y sin asignar">
<title>Distribución de cuentas por usuario</title><circle class="donut-track" cx="60" cy="60" r="46"/>
<?php if($total): ?><circle class="account-donut-secondary" cx="60" cy="60" r="46"/><?php endif ?>
<?php foreach($linkAccounts['users'] as $index=>$user): $share=$total?100*$user['accounts']/$total:0; ?>
<circle cx="60" cy="60" r="46" pathLength="100" stroke="var(<?= $palette[$index%count($palette)] ?>)" stroke-dasharray="<?= $share ?> <?= 100-$share ?>" stroke-dashoffset="<?= -$offset ?>" transform="rotate(-90 60 60)"><title><?= $escape($user['display_name']??$user['usuario']) ?>: <?= $user['accounts'] ?> cuentas</title></circle>
<?php $offset+=$share;endforeach ?>
<text class="donut-total" x="60" y="59"><?= $total ?></text><text class="donut-caption" x="60" y="76">cuentas</text></svg>
<div class="account-chart-summary"><ul class="account-user-legend" aria-label="Cuentas asignadas por usuario">
<?php foreach($linkAccounts['users'] as $index=>$user): ?><li class="<?= $user['eligible']?'':'account-no-access' ?>"><span class="account-dot" style="background:var(<?= $palette[$index%count($palette)] ?>)"></span><span class="account-user-name <?= $user['eligible']?'':'text-danger fw-bold' ?>" <?= $user['eligible']?'':'title="Sin acceso"' ?>><?= $escape($user['display_name']??$user['usuario']) ?></span><strong><?= $total?round(100*$user['accounts']/$total,1):0 ?>% <small>(<?= (int)$user['accounts'] ?>)</small></strong></li><?php endforeach ?>
</ul><div class="account-legend-row"><span class="account-dot account-dot-secondary"></span><span>Sin asignar</span><strong><?= $total?round(100*$linkAccounts['unassigned']/$total,1):0 ?>% <small>(<?= $linkAccounts['unassigned'] ?>)</small></strong></div></div>
</div><?php endif ?>
</div>
<button type="button" class="dashboard-link" data-open-module="Link">Ir a Link Netflix <span aria-hidden="true">→</span></button>
</article>
