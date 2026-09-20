<div class="contenedor">

    <div class="module-heading"><h2>Soporte</h2><span class="module-category">Servicios</span></div>

    <div class="soporte-container">

        <div class="soporte-form card card-body">
            <input class="form-control form-control-sm" type="email" id="correo_soporte" aria-label="Correo a consultar" placeholder="Ingrese su correo" required>
            <button type="button" class="btn-action btn-new btn-soporte btn btn-sm btn-primary">Buscar Correos</button>
        </div>

    </div>

</div>


<div id="modalSoporte" class="modal" tabindex="-1" aria-labelledby="tituloModalSoporte" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h3 class="modal-title fs-6" id="tituloModalSoporte">Correos</h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="resultadoSoporte" aria-live="polite"></div>
      <div class="modal-footer py-2"><button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
    </div>
  </div>
</div>
