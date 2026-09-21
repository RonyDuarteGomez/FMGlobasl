<?php
$charts=[['Pagos',$spotifySummary['payments'],['current'=>'Al día','soon'=>'Por vencer','expired'=>'Vencidos'],['--bs-success','--bs-warning','--bs-danger'],'pagos'],['Renovaciones',$spotifySummary['renewals'],['current'=>'Al día','soon'=>'Por vencer','expired'=>'Vencidas'],['--bs-success','--bs-warning','--bs-danger'],'renovaciones'],['Cuentas',$spotifySummary['accounts'],['assigned'=>'Asignadas','free'=>'Libres','fallen'=>'Caídas'],['--color-primario','--bs-success','--bs-danger'],'cuentas']];
?>
<article class="dashboard-card card card-outline card-primary spotify-dashboard-card">
<div class="dashboard-card-heading"><h3>Spotify</h3></div>
<div class="spotify-dashboard-charts">
<?php foreach($charts as [$title,$values,$labels,$colors,$unit]): $total=array_sum($values);$offset=0; ?>
<div class="spotify-chart-block"><div class="account-chart">
<svg class="platform-donut" viewBox="0 0 120 120" role="img" aria-label="<?= $escape($title.': '.implode(', ',array_map(fn($key)=>$labels[$key].' '.$values[$key],array_keys($labels)))) ?>">
<circle class="donut-track" cx="60" cy="60" r="46"/>
<?php foreach(array_keys($labels) as $i=>$key): $share=$total?100*$values[$key]/$total:0; if($share): ?>
<circle cx="60" cy="60" r="46" pathLength="100" fill="none" stroke="var(<?= $colors[$i] ?>)" stroke-width="12" stroke-dasharray="<?= $share ?> <?= 100-$share ?>" stroke-dashoffset="<?= -$offset ?>" transform="rotate(-90 60 60)"/>
<?php endif; $offset+=$share; endforeach ?>
<text class="donut-total" x="60" y="59"><?= $total ?></text><text class="donut-caption" x="60" y="76"><?= $unit ?></text></svg>
<div class="account-chart-summary">
<?php foreach(array_keys($labels) as $i=>$key): ?><div class="account-legend-row"><span class="account-dot" style="background:var(<?= $colors[$i] ?>)"></span><span><?= $labels[$key] ?></span><strong><?= $total?round(100*$values[$key]/$total,1):0 ?>% <small>(<?= $values[$key] ?>)</small></strong></div><?php endforeach ?>
</div></div></div>
<?php endforeach ?>
</div><button type="button" class="dashboard-link" data-open-module="Spotify">Ir a Spotify <span aria-hidden="true">→</span></button>
</article>
