<?php
$dates=array_keys($series); $peak=0; $totalChart=0;
foreach($series as $counts) { $peak=max($peak,...array_values($counts)); $totalChart+=array_sum($counts); }
$step=max(1,(int)ceil($peak/4)); $ceiling=$step*4;
$x=static fn(int $i)=>42+($i/max(1,count($dates)-1))*934;
$y=static fn(int $value)=>170-($value/$ceiling)*158;
?>
<div class="report-service-legend"><?php foreach($chartServices as [$label,$color]): ?><span><i class="service-dot" style="background:<?= $escape($color) ?>"></i><?= $escape($label) ?></span><?php endforeach ?></div>
<div class="report-service-chart">
<svg viewBox="0 0 1000 200" preserveAspectRatio="none" role="img" aria-labelledby="serviceChartTitle serviceChartDesc">
 <title id="serviceChartTitle"><?= $escape($reportTitle) ?> <?= $chartDays ? "durante $chartDays días" : "en todo el historial" ?></title>
 <desc id="serviceChartDesc">Fechas en el eje horizontal y número de consultas en el vertical. <?= $totalChart ?> consultas en el período.</desc>
 <?php for($tick=0;$tick<=4;$tick++): $value=$tick*$step; $cy=$y($value); ?>
 <line x1="42" y1="<?= $cy ?>" x2="976" y2="<?= $cy ?>" class="chart-grid"/>
 <text x="32" y="<?= $cy+4 ?>" text-anchor="end" class="chart-label"><?= $value ?></text>
 <?php endfor ?>
 <?php for($column=0;$column<=8;$column++): $gridX=42+934*$column/8; ?>
 <line x1="<?= $gridX ?>" y1="12" x2="<?= $gridX ?>" y2="170" class="chart-grid"/>
 <?php endfor ?>
 <?php foreach($chartServices as $service=>[$label,$color]): $points=[]; foreach($dates as $i=>$date) $points[]=$x($i).','.$y($series[$date][$service]); ?>
 <polyline points="<?= implode(' ',$points) ?>" fill="none" stroke="<?= $color ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
 <?php endforeach ?>
 <?php $ticks=array_unique([0,(int)floor((count($dates)-1)/4),(int)floor((count($dates)-1)/2),(int)floor(3*(count($dates)-1)/4),count($dates)-1]); foreach($ticks as $i): ?>
 <text x="<?= $x($i) ?>" y="192" text-anchor="middle" class="chart-label"><?= (new \DateTimeImmutable($dates[$i]))->format('d/m') ?></text>
 <?php endforeach ?>
</svg>
</div>
<?php if(!$totalChart): ?><p class="small text-body-secondary text-center mb-0">No hay consultas que coincidan con los filtros seleccionados.</p><?php endif ?>