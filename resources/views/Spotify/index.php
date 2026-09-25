<section class="contenedor gmail-page spotify-page" id="spotifyModule" data-admin="<?= $isAdmin?'1':'0' ?>">
 <div class="module-heading"><h2>Spotify</h2><span class="module-category">Gestión</span></div>
 <div id="spotifyNotifications" hidden></div>
 <div id="spotifyNotice" class="alert alert-info" role="status" hidden></div>
 <?php if($isAdmin): ?><div id="spotifyAttention" class="alert alert-warning" role="status" hidden></div><?php endif ?>
 <?php if($isAdmin): ?><div class="d-flex align-items-center gap-3 mb-2"><div class="btn-group" role="group" aria-label="Modo Spotify"><input type="radio" class="btn-check" name="spotifyMode" id="spotifyModeSales" checked><label class="btn btn-sm btn-outline-primary" for="spotifyModeSales">Ventas</label><input type="radio" class="btn-check" name="spotifyMode" id="spotifyModePayments"><label class="btn btn-sm btn-outline-primary" for="spotifyModePayments">Pagos</label></div></div><?php endif ?>
  <div class="section-toolbar spotify-toolbar">
   <?php if($isAdmin): ?><button type="button" class="btn btn-sm btn-primary gmail-authorize" data-spotify-action="new">Agregar cuenta principal <span aria-hidden="true">＋</span></button><?php endif ?>
   <?php if(!$isAdmin): ?><button type="button" class="btn btn-sm btn-primary" data-spotify-action="obtain">Obtener cuenta</button><?php endif ?>
   <?php if($isAdmin): ?><button type="button" class="btn btn-sm btn-outline-primary" data-spotify-action="import">Importar CSV</button><button type="button" class="btn btn-sm btn-outline-primary" data-spotify-action="transfer_bulk">Reasignar cuentas</button><?php endif ?>
   <span id="spotifyAvailable" class="small text-body-secondary" aria-live="polite"></span>
  </div>
 <div id="spotifySalesPanel" class="card card-body">
  <div class="tablaOpciones spotify-filters">
   <input id="spotifySearch" class="form-control form-control-sm" type="search" maxlength="100" placeholder="Buscar correo, cliente, celular o perfil" aria-label="Buscar">

   <?php if($isAdmin): ?><select id="spotifyState" class="form-select form-select-sm" aria-label="Estado"><option value="">Todos los estados</option><option value="available">Disponibles</option><option value="assigned">Asignadas</option><option value="fallen">Caídas</option></select><select id="spotifyAdvisor" class="form-select form-select-sm" aria-label="Asesor"><option value="">Todos los asesores</option></select><?php endif ?>
   <select id="spotifyExpiry" class="form-select form-select-sm" aria-label="Vencimiento del cliente"><option value="">Todos los vencimientos</option><option value="current">Al día</option><option value="soon">Por vencer</option><option value="expired">Vencidos</option></select>
   <select id="spotifySize" class="form-select form-select-sm selectRegistros" aria-label="Registros por página"><option value="10">10 registros</option><option value="20" selected>20 registros</option><option value="50">50 registros</option></select>
  </div>
  <div class="table-responsive"><table id="spotifyTable" class="tabla table table-sm table-hover align-middle"><thead><tr><?php if($isAdmin): ?><th class="spotify-payment-data">Datos de Pago</th><?php endif ?><th>Cuenta</th><th>Contraseña</th><th>Perfil</th><th>Estado</th><th>Asesor</th><th>Cliente / celular</th><th>Inicio Servicio</th><th>Última renovación</th><th>Vencimiento</th><th>Días servicio</th><th>Acción</th></tr></thead><tbody></tbody></table></div>
  <div class="table-footer"><span id="spotifyCount" class="small text-body-secondary"></span><div id="spotifyPages" class="paginacion"></div></div>
 </div>
 <?php if($isAdmin): ?><div id="spotifyPaymentsPanel" class="card card-body" hidden>
  <div class="tablaOpciones spotify-filters mb-2"><input type="search" id="spotifyPaymentSearch" class="form-control form-control-sm" maxlength="100" placeholder="Buscar correo principal o de pago" aria-label="Buscar pagos"><select id="spotifyPaymentState" class="form-select form-select-sm" aria-label="Estado de pago"><option value="">Todos</option><option value="current">Al día</option><option value="soon">Por vencer</option><option value="expired">Vencidos</option></select><select id="spotifyPaymentSize" class="form-select form-select-sm" aria-label="Registros de pagos"><option>10</option><option selected>20</option><option>50</option></select><button type="button" class="btn btn-sm btn-primary" id="spotifyBulkPay" disabled>Registrar pagos (0)</button></div>
  <div class="table-responsive"><table id="spotifyPaymentsTable" class="tabla table table-sm table-hover align-middle"><thead><tr><th><input type="checkbox" id="spotifyPaymentsAll" aria-label="Seleccionar página visible"></th><th>Correo principal</th><th>Correo de pago</th><th>Próximo pago</th><th>Estado</th><th>Cuentas secundarias</th><th>Asignadas</th><th>Caídas</th><th>Libres</th><th>Acción</th></tr></thead><tbody></tbody></table></div>
  <div class="table-footer"><span id="spotifyPaymentCount" class="small text-body-secondary"></span><div id="spotifyPaymentPages" class="paginacion"></div></div>
 </div><?php endif ?>

 <div class="modal fade" id="spotifyModal" tabindex="-1" aria-labelledby="spotifyModalTitle" data-bs-backdrop="static"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg"><div class="modal-content">
  <div class="modal-header"><h3 class="modal-title fs-5" id="spotifyModalTitle"></h3><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
  <form id="spotifyForm"><div class="modal-body"><div id="spotifyError" class="alert alert-danger" role="alert" hidden></div><div id="spotifyBusy" class="text-center py-3" role="status" hidden><span class="spinner-border text-primary"></span><p class="mb-0">Procesando…</p></div><div id="spotifyFields"></div><div id="spotifyImportReport" class="fm-import-result" role="status" hidden></div></div>
   <div class="modal-footer"><button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" id="spotifyClose">Cerrar</button><button type="submit" class="btn btn-sm btn-primary" id="spotifySubmit">Guardar</button></div>
  </form>
 </div></div></div>
</section>
