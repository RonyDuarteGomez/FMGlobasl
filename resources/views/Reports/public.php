<?php $escape=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); ?>
<section class="public-report-page">
 <div class="module-heading"><h2><?= $escape($reportTitle) ?></h2><span class="module-category">Reportes</span></div>
 <div class="card card-body">

  <?php require FM_ROOT.'/resources/views/Reports/service-chart.php'; ?>
 </div>
 <div class="card card-body">
  <form class="tablaOpciones" data-module-filter="<?= $escape($route) ?>" id="publicReportFilters">
   <?php if($reportKind!=='public'): ?><input type="hidden" name="report" value="<?= $escape($reportKind) ?>"><?php endif ?>
   <input class="form-control form-control-sm" type="search" name="q" maxlength="100" value="<?= $escape($search) ?>" placeholder="Buscar correo, usuario o servicio..." aria-label="Buscar consultas">
   <select class="form-select form-select-sm" name="days" aria-label="Período"><?php foreach([7=>'Últimos 7 días',30=>'Últimos 30 días',60=>'Últimos 60 días',90=>'Últimos 90 días',0=>'Todo el historial'] as $value=>$label): ?><option value="<?= $value ?>" <?= $days===$value?'selected':'' ?>><?= $label ?></option><?php endforeach ?></select>
   <select class="form-select form-select-sm" name="size" aria-label="Registros por página"><?php foreach([10,20,50] as $value): ?><option value="<?= $value ?>" <?= $size===$value?'selected':'' ?>><?= $value ?> registros</option><?php endforeach ?></select>
  </form>
  <div class="table-responsive"><table class="tabla table table-sm table-hover align-middle"><thead><tr><?php if($reportKind==='links'): ?><th>Fecha</th><th>Correo</th><th>Resultado</th><th>Usuario</th><?php else: ?><th>Fecha</th><th>Correo</th><th>Servicio</th><th>Resultados</th><th>Usuario</th><?php endif ?></tr></thead><tbody>
   <?php foreach($rows as $row): ?><tr><?php if($reportKind==='links'): ?>
   <td class="text-nowrap"><?= $escape(\FMGlobal\Support\DisplayDate::dateTime($row['fecha'])) ?></td><td><?= $escape($row['correo']) ?></td><td><span class="badge text-white <?= $row['generated']?'bg-success':'bg-danger' ?>"><?= $row['generated']?'Generado':'No generado' ?></span></td><td><?= $escape($row['display_name']??$row['usuario']) ?></td>
   <?php else: ?><td class="text-nowrap"><?= $escape(\FMGlobal\Support\DisplayDate::dateTime($row['fecha'])) ?></td><td><?= $escape($row['correo']) ?></td><td><?= $escape($chartServices[(int)$row['streaming']][0]??'Servicio') ?></td><td><?= (int)$row['num_urls'] ?></td><td><?= $escape($row['display_name']??$row['usuario']??'Clientes') ?></td><?php endif ?></tr><?php endforeach ?>
   <?php if(!$rows): ?><tr><td colspan="<?= $reportKind==='links'?4:5 ?>">No hay consultas que coincidan con los filtros.</td></tr><?php endif ?>
  </tbody></table></div>
  <div class="paginacion table-footer"><span class="small text-body-secondary"><?= $result['total'] ?> registros · Página <?= $page ?> de <?= $pages ?></span>
  <?php if($pages>1): $numbers=array_unique(array_merge([1],range(max(1,$page-2),min($pages,$page+2)),[$pages])); foreach($numbers as $number): ?>
   <button type="button" class="pagination-button btn btn-sm btn-outline-primary <?= $number===$page?'is-active':'' ?>" <?= $number===$page?'aria-current="page"':'' ?> data-load-url="<?= $escape($pageUrl($number)) ?>"><?= $number ?></button>
  <?php endforeach; endif ?>
  </div>
 </div>
</section>