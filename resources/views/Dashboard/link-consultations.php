<article class="dashboard-card card card-outline card-primary dashboard-activity-card link-consultations-card">
<div class="dashboard-card-heading"><h3>Consultas link</h3><span class="metric-period">Últimos 30 días</span></div>
<div class="secondary-metrics"><div class="card-metric"><?= (int)$linkConsultations['generated'] ?><span>Consultas</span></div></div>
<figure class="daily-activity links-activity"><figcaption>Actividad diaria</figcaption><?= \FMGlobal\Support\DailyBars::render($linkConsultations['generationSeries']) ?></figure>

<?php if(!empty($permissions['reports.links'])): ?><button type="button" class="dashboard-link" data-open-module="reporteLinks">Ver reporte detallado <span aria-hidden="true">→</span></button><?php endif ?>
</article>
