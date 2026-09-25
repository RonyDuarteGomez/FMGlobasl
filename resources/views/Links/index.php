<div class="contenedor gmail-page links-page" id="linkAccounts" data-admin="<?= $isAdmin?'1':'0' ?>">
 <div class="module-heading"><h2>Link Netflix</h2><span class="module-category">Gestión</span></div>
 <div id="linkNotice" class="alert alert-info" role="status" hidden></div>
 <div id="linkOrphanWarning" class="alert alert-warning" hidden><svg class="link-warning-icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="var(--bs-warning)" d="M12 2 1 22h22L12 2Z"/><path stroke="#212529" stroke-width="2" d="M12 8v7m0 2v2"/></svg><span id="linkOrphanText"></span></div>
 <div class="cardHome tablaHome card card-body">
 <?php if($isAdmin): ?><div class="section-toolbar link-toolbar">
 <button class="btn btn-sm btn-primary gmail-authorize" data-link-action="new">Agregar cuenta <span aria-hidden="true">＋</span></button>
 <button class="btn btn-sm btn-outline-primary" data-link-action="import">Importar CSV</button>
 <button class="btn btn-sm btn-outline-primary" data-link-action="bulk">Gestionar asignaciones</button>
 </div><?php endif ?>
 <div class="tablaOpciones link-filters">
 <input id="linkSearch" type="search" class="form-control form-control-sm" placeholder="Buscar correo o usuario..." aria-label="Buscar cuentas">
 <select id="linkStatus" class="form-select form-select-sm" aria-label="Estado"><option value="">Todos los estados</option><option value="active">Activo</option><option value="no_link">No Link</option><option value="manual_error">Error</option></select>
 <?php if($isAdmin): ?><select id="linkOwner" class="form-select form-select-sm" aria-label="Asignación"><option value="">Todos los usuarios</option><option value="none">Sin asignar</option></select><?php endif ?>
 <select id="linkSize" class="form-select form-select-sm selectRegistros" aria-label="Registros por página"><option value="10">10 registros</option><option value="20" selected>20 registros</option><option value="50">50 registros</option></select>
 </div>
 <div class="tablaContainer table-responsive"><table class="tabla table table-sm table-hover align-middle" id="linkTable"><thead><tr><th>N°</th><th>Correo : contraseña</th><th>Estado</th><th>Usuario asignado</th><th>Fecha de asignación</th><th>Acción</th></tr></thead><tbody><tr><td colspan="6">Cargando…</td></tr></tbody></table></div>
 <div class="table-footer"><small id="linkCount" class="text-body-secondary"></small><div id="linkPages" class="paginacion"></div></div>
 </div>
 <div class="modal fade" id="linkAccountModal" tabindex="-1" aria-labelledby="linkModalTitle" data-bs-backdrop="static">
 <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
 <div class="modal-header"><h3 class="modal-title fs-5" id="linkModalTitle"></h3><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
 <form id="linkAccountForm"><div class="modal-body">
 <div id="linkModalError" class="alert alert-danger" role="alert" hidden></div>
 <div id="linkBusy" class="text-center py-3" role="status" hidden><div class="spinner-border text-primary mb-2"></div><p class="mb-0">Procesando…</p></div>
 <div data-link-panel="edit" hidden>
 <input type="hidden" name="id"><input type="hidden" name="revision">
 <div class="link-compact-fields">
 <div class="link-credential-field"><label class="form-label" for="linkCredentials">Correo : contraseña <span class="text-danger">*</span></label><input id="linkCredentials" name="credentials" class="form-control form-control-sm" maxlength="1024" autocomplete="off" spellcheck="false" placeholder="correo@ejemplo.com:contraseña"></div>
 <div class="link-clear-tokens"><button type="button" id="linkClearTokens" class="btn btn-sm btn-outline-secondary" hidden><i class="fa-solid fa-eraser" aria-hidden="true"></i> Limpiar ID y secure</button></div>
 <div><label class="form-label" for="linkExternalId">ID <span class="text-danger">*</span></label><textarea id="linkExternalId" name="external_id" class="form-control form-control-sm" rows="2" maxlength="8000" spellcheck="false" autocomplete="off" placeholder="Pegar ID"></textarea></div>
 <div><label class="form-label" for="linkSecure">secure <span class="text-danger">*</span></label><textarea id="linkSecure" name="secure" class="form-control form-control-sm" rows="2" maxlength="8000" spellcheck="false" autocomplete="off" placeholder="Pegar secure"></textarea></div>
 </div>
 </div>
 <div data-link-panel="import" hidden>
 <div class="fm-import-heading"><label class="form-label" for="linkCsv">Archivo CSV <span class="text-danger">*</span></label><a class="btn btn-sm btn-outline-primary" href="soporte/link.php?action=template" download="modelo-cuentas-link.csv"><i class="fa-solid fa-download" aria-hidden="true"></i> Descargar modelo</a></div>
 <input type="file" id="linkCsv" name="csv" class="form-control form-control-sm" accept=".csv,text/csv" aria-describedby="linkCsvHelp">
 <div id="linkCsvHelp" class="fm-import-help"><div class="fm-import-limits"><span>UTF-8</span><span>Máx. 5 MB</span><span>Hasta 5000 filas</span></div><div class="fm-import-format"><span>Columnas</span><code>ID</code><code>secure</code><code>correo_contrasena</code></div><p>Correo y contraseña separados por <strong>:</strong>. Las cuentas se importan activas y sin asignar.</p></div>
 <div id="linkImportResult" class="fm-import-result" aria-live="polite"></div>
 </div>
 <div data-link-panel="move" hidden>
 <input type="hidden" name="account_id">
 <div class="link-move-fields">
 <div class="link-move-operation"><label for="linkOperation" class="form-label">Operación</label><select name="operation" id="linkOperation" class="form-select form-select-sm"><option value="assign">Asignar cuentas disponibles</option><option value="release">Liberar cuentas de un usuario</option><option value="transfer">Trasladar a otro usuario</option></select></div>
 <div class="link-move-users">
 <div id="linkSourceGroup"><label class="form-label" for="linkSource">Usuario origen</label><select name="source" id="linkSource" class="form-select form-select-sm"></select></div>
 <div id="linkTargetGroup"><label class="form-label" for="linkTarget">Usuario destino</label><select name="target" id="linkTarget" class="form-select form-select-sm"></select></div>
 </div>
 <div class="link-move-quantity"><div><label class="form-label" for="linkQuantity">Cantidad</label><input id="linkQuantity" name="quantity" class="form-control form-control-sm" type="number" min="1" max="100000" value="1"></div><div class="link-move-options">
 <div class="form-check" id="linkAllGroup"><input type="checkbox" id="linkAll" class="form-check-input"><label class="form-check-label" for="linkAll">Todas las disponibles</label></div>
 <div class="form-check" id="linkIncludeErrorGroup"><input type="checkbox" name="include_error" id="linkIncludeError" value="1" class="form-check-input"><label for="linkIncludeError" class="form-check-label">Incluir con error</label></div>
 </div></div>
 </div>
 <p id="linkAvailability" class="link-availability" aria-live="polite"></p>
 <div id="linkMovePreview" class="alert alert-info" hidden></div>
 </div>
 <div data-link-panel="result" hidden><label class="form-label" for="generatedLink">Enlace generado</label><textarea readonly class="form-control form-control-sm mb-2" id="generatedLink" rows="2"></textarea><p id="linkExpiry" class="small text-body-secondary"></p><div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-primary" id="linkCopy"><i class="fa-regular fa-copy" aria-hidden="true"></i> Copiar</button><a class="btn btn-sm btn-outline-primary" id="linkOpen" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Abrir</a></div></div>
 </div><div class="modal-footer"><button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-sm btn-primary" id="linkSubmit">Guardar</button></div></form>
 </div></div></div>
</div>
