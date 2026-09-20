<div class="contenedor">

    <div class="module-heading"><h2>Asesor</h2><span class="module-category">Servicios</span></div>

    <div class="asesor-container">

        <div class="asesor-item card card-body card-outline card-primary">
            <img src="assets/img/tarjeta_netflix.png" alt="Netflix">
            <h2>Valida código de Acceso Netflix</h2>

            <div class="asesor-form">
                <input class="form-control form-control-sm" type="email" id="correo_netflix1" placeholder="Ingrese su correo" required>
                <button type="button" class="btn-action btn-new btn-netflix1 btn btn-sm btn-primary">Buscar Código</button>
            </div>
        </div>


        <div class="asesor-item card card-body card-outline card-primary">
            <img src="assets/img/tarjeta_disney.png" alt="Disney+">
            <h2>Valida código de Acceso Disney+</h2>

            <div class="asesor-form">
                <input class="form-control form-control-sm" type="email" id="correo_disney" placeholder="Ingrese su correo" required>
                <button type="button" class="btn-action btn-new btn-disney btn btn-sm btn-primary">Buscar Código</button>
            </div>
        </div>

        <div class="asesor-item card card-body card-outline card-primary">
            <img src="assets/img/netflix_codigo.png" alt="Netflix">
            <h2>Valida código de Inicio Netflix</h2>

            <div class="asesor-form">
                <input class="form-control form-control-sm" type="email" id="correo_netflix2" placeholder="Ingrese su correo" required>
                <button type="button" class="btn-action btn-new btn-netflix2 btn btn-sm btn-primary">Buscar Código</button>
            </div>
        </div>

    </div>

</div>



<div id="modalAsesor" class="modal" tabindex="-1" aria-labelledby="tituloModalAsesor" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h3 class="modal-title fs-6" id="tituloModalAsesor">Resultado</h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="resultadoAsesor" aria-live="polite"></div>
      <div class="modal-footer py-2"><button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
    </div>
  </div>
</div>
