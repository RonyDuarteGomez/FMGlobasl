<section class="<?= $mode==='rules'?'permissions-page':($mode==='report'?'public-report-page':'contenedor gmail-page') ?>" id="externalModule" data-mode="<?= $mode ?>">
<div class="module-heading"><div class="d-flex align-items-center flex-wrap gap-2"><h2><?= ['rules'=>'Permisos de Generador de Link','report'=>'Generador de Link','generate'=>'Generador de Link'][$mode] ?></h2><?php if($mode==='generate'): ?><span id="externalServiceState" class="badge text-white" role="status" hidden></span><?php endif ?></div><span class="module-category"><?= ['rules'=>'Mantenimiento','report'=>'Reportes','generate'=>'Servicios'][$mode] ?></span></div>
<div id="externalNotice" class="alert" hidden role="status"></div>
<?php if($mode==='generate'): ?>
<div class="card card-body"><div id="externalStatus" class="alert alert-info"></div><button id="externalRequestBrowser" class="btn btn-sm btn-outline-danger" type="button" hidden>Solicitar cambio de navegador</button>
<form id="externalGenerateForm" hidden>
<div class="row g-2">
<div class="col-12"><label for="externalId" class="form-label">ID <span class="text-danger">*</span></label><textarea id="externalId" class="form-control form-control-sm" rows="3" maxlength="8192" required autocomplete="off" spellcheck="false"></textarea></div>
<div class="col-12"><label for="externalSecure" class="form-label">Secure <span class="text-danger">*</span></label><textarea id="externalSecure" class="form-control form-control-sm" rows="3" maxlength="8192" required autocomplete="off" spellcheck="false"></textarea></div>
</div>
<div class="d-flex flex-wrap gap-2 mt-3">
<button id="externalGenerate" class="btn btn-sm btn-primary px-4" type="submit" disabled>Generar link</button>
<button id="externalClear" class="btn btn-sm btn-outline-secondary" type="button">Limpiar</button>
<button id="externalCopy" class="btn btn-sm btn-outline-primary" type="button" disabled>Copiar link</button>
<a id="externalOpen" class="btn btn-sm btn-outline-primary disabled" aria-disabled="true" tabindex="-1" target="_blank" rel="noopener noreferrer">Abrir link</a>
</div>
<div id="externalResult" class="mt-3" hidden><label for="externalGeneratedLink" class="form-label">Link generado</label><input id="externalGeneratedLink" class="form-control form-control-sm" readonly></div>
</form></div>
<?php elseif($mode==='rules'): ?>
<div id="externalPendingWarning" class="alert alert-warning" role="status" aria-live="polite" hidden><svg class="link-warning-icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="var(--bs-warning)" d="M12 2 1 22h22L12 2Z"/><path stroke="#212529" stroke-width="2" d="M12 8v7m0 2v2"/></svg><span id="externalPendingNames"></span></div>
<div class="card card-body"><div class="permission-preview-toolbar">
<input type="hidden" id="externalTargetType" value="role">
<div class="permission-preview-mode"><div class="btn-group" role="group" aria-label="Configurar permisos de">
<input class="btn-check" type="radio" name="externalTargetMode" id="externalModeRole" value="role" checked><label class="btn btn-sm btn-outline-primary" for="externalModeRole">Perfiles</label>
<input class="btn-check" type="radio" name="externalTargetMode" id="externalModeUser" value="user"><label class="btn btn-sm btn-outline-primary" for="externalModeUser">Usuarios</label>
</div></div>
<div class="permission-preview-selection"><select id="externalTargetId" class="form-select form-select-sm" aria-label="Perfil o usuario"></select>
<div id="externalUserPicker" class="dropdown external-user-picker" hidden><button id="externalUserPickerButton" class="form-select form-select-sm text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Seleccionar usuario"></button><div id="externalUserOptions" class="dropdown-menu w-100" aria-labelledby="externalUserPickerButton"></div></div>
<button id="externalRestoreProfile" class="btn btn-sm btn-outline-secondary" type="button" hidden disabled>Restaurar a permisos de perfil</button>
<button id="externalSave" class="btn btn-sm btn-primary" type="submit" form="externalRulesForm" disabled>Guardar cambios</button></div></div>
<div id="externalEffective" class="small text-body-secondary mb-2" role="status">Los cambios se aplican al guardar.</div>
<form id="externalRulesForm">
<?php foreach(['Schedule'=>['Horario permitido','fa-clock','Define los días y las horas en que se permite generar links. Hora de Lima.'],'Limit'=>['Intentos diarios','fa-hashtag','Cada generación consume un intento, aunque no devuelva un link. El cupo se reinicia a medianoche de Lima.'],'Browser'=>['Navegador autorizado','fa-window-maximize','Limita el servicio a un navegador por usuario. El primer registro es directo; los cambios requieren autorización.']] as $key=>[$label,$icon,$description]): ?>
<details class="permission-preview-group external-rule-group" id="externalGroup<?= $key ?>">
<summary><i class="fa-solid <?= $icon ?> me-2" aria-hidden="true"></i><span><?= $label ?></span><span class="external-rule-summary"><small id="external<?= $key ?>Source"></small><span id="external<?= $key ?>Status" class="badge bg-secondary text-white">Cargando</span></span></summary>
<div class="external-rule-body"><p class="external-rule-description"><?= $description ?></p>
<select id="external<?= $key ?>Mode" hidden aria-label="Configuración de <?= $label ?>"><option value="inherit">Usar configuración del perfil</option><option value="off">Sin restricción</option><option value="on">Personalizar</option></select>
<div id="external<?= $key ?>Choices" class="external-rule-choices"><div class="btn-group" role="group" aria-label="<?= $label ?>">
<button id="external<?= $key ?>Inheritance" type="button" class="btn btn-sm btn-outline-secondary" data-rule="<?= $key ?>" data-rule-choice="inherit" hidden>Usar perfil</button>
<?php foreach(['off'=>['Schedule'=>'Sin restricción','Limit'=>'Sin límite','Browser'=>'Cualquier navegador'],'on'=>['Schedule'=>'Configurar horario','Limit'=>'Limitar intentos','Browser'=>'Un solo navegador']] as $choice=>$labels): ?><button type="button" class="btn btn-sm btn-outline-secondary" data-rule="<?= $key ?>" data-rule-choice="<?= $choice ?>"><?= $labels[$key] ?></button><?php endforeach ?>
</div></div>
<div id="external<?= $key ?>Detail" class="external-rule-detail"></div>
<?php if($key==='Schedule'): ?><div id="externalWeek" class="mt-3"></div>
<?php elseif($key==='Limit'): ?><div id="externalLimitFields" class="external-rule-control mt-2" hidden><label for="externalLimit" class="form-label">Máximo diario por usuario</label><input id="externalLimit" type="number" min="1" max="100000" value="20" class="form-control form-control-sm" required></div>
<?php else: ?><div id="externalBrowsers" class="mt-3"></div><?php endif ?>
</div></details><?php endforeach ?>
</form></div>
<div class="modal fade" id="externalHoursModal" tabindex="-1" aria-labelledby="externalHoursTitle" data-bs-backdrop="static"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
<div class="modal-header"><h3 id="externalHoursTitle" class="modal-title">Horarios</h3><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
<form id="externalHoursForm"><div class="modal-body"><div id="externalHoursError" class="alert alert-danger" role="alert" hidden></div><div id="externalHoursRows"></div>
<button id="externalHoursAdd" type="button" class="btn btn-sm btn-outline-primary">Agregar horario</button>
<div class="form-check mt-3"><input class="form-check-input" type="checkbox" id="externalHoursCopy"><label class="form-check-label small" for="externalHoursCopy">Copiar a todos los días</label></div>
<p class="small text-body-secondary mt-2 mb-0">Hora de Lima. Usa 24:00 para el final del día. Se aplica al pulsar Guardar cambios en la pantalla principal.</p></div>
<div class="modal-footer"><button class="btn btn-sm btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-sm btn-primary" type="submit">Guardar horarios</button></div></form>
</div></div></div>
<?php else: ?>
<div class="card card-body"><div class="report-service-legend"><span><i class="service-dot" style="background:var(--bs-primary)"></i>Intentos de generación</span></div><div id="externalChart" class="report-service-chart"></div><p id="externalChartEmpty" class="small text-body-secondary text-center mb-0" hidden>No hay consultas que coincidan con los filtros seleccionados.</p></div>
<div class="card card-body">
<div class="tablaOpciones sales-filters"><input id="externalSearch" type="search" class="form-control form-control-sm" placeholder="Buscar usuario" aria-label="Buscar usuario" maxlength="100"><select id="externalPeriod" class="form-select form-select-sm" aria-label="Periodo"><option value="today">Hoy</option><option value="week">Última semana</option><option value="month" selected>Último mes</option><option value="six">Últimos 6 meses</option><option value="year">Último año</option><option value="custom">Fechas específicas</option></select><div class="sales-date-filters"><div><label for="externalFrom" class="form-label">Fecha de inicio</label><input id="externalFrom" type="date" class="form-control form-control-sm"></div><div><label for="externalTo" class="form-label">Fecha de fin</label><input id="externalTo" type="date" class="form-control form-control-sm"></div></div><select id="externalSize" class="form-select form-select-sm" aria-label="Registros por página"><option value="10">10 registros</option><option value="20" selected>20 registros</option><option value="50">50 registros</option></select></div>
<div class="table-responsive"><table class="tabla table table-sm table-hover align-middle" id="externalTable"><thead><tr><th>Fecha</th><th>Resultado</th><th>Usuario</th></tr></thead><tbody></tbody></table></div><div class="table-footer"><span id="externalCount" class="small text-body-secondary"></span><div id="externalPages" class="paginacion"></div></div></div>
<?php endif ?>
</section>
