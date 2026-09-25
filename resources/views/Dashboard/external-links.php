<?php
$series=[];$daily=array_column($externalSummary['daily'],'total','day');
for($day=new DateTimeImmutable($externalSummary['from']);$day<=new DateTimeImmutable($externalSummary['to']);$day=$day->modify('+1 day'))$series[$day->format('Y-m-d')]=(int)($daily[$day->format('Y-m-d')]??0);
?>
<article class="dashboard-card card card-outline card-primary dashboard-activity-card">
<div class="dashboard-card-heading"><h3>Generador de Link</h3><span class="metric-period">Últimos 30 días</span></div>
<div class="secondary-metrics"><div class="card-metric"><?= (int)$externalSummary['total'] ?><span>Consultas</span></div></div>
<figure class="daily-activity"><figcaption>Actividad diaria</figcaption><?= \FMGlobal\Support\DailyBars::render($series) ?></figure>
<?php if(!empty($permissions['reports.external_links'])): ?><button type="button" class="dashboard-link" data-open-module="ReporteExterno">Ver reporte detallado <span aria-hidden="true">→</span></button><?php endif ?>
</article>
