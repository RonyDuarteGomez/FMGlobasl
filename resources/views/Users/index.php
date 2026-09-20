

<div id="contenidoUsuarios" class="users-page">
    <div class="module-heading"><h2>Usuarios</h2><span class="module-category">Mantenimiento</span></div>
    <div class="card card-body"><div class="users-heading">
    <button id="btnNuevo" class="btn btn-sm btn-primary table-add">Nuevo usuario <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></button></div>
    <div class="tabla-wrapper">
        <div id="tablaUsuarios"></div></div>
    </div>
</div>

<div id="modalUsuario" class="modal" tabindex="-1" aria-labelledby="tituloModalUsuario" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable user-dialog">
  <div class="modal-content">
   <div class="modal-header py-2"><h3 class="modal-title fs-6" id="tituloModalUsuario">Nuevo usuario</h3><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
   <div class="modal-body">
    <form id="usuarioForm">
     <?= \FMGlobal\Security\Csrf::field() ?>
     <input type="hidden" name="usuario_id" id="usuario_id"><input type="hidden" name="personal_id" id="personal_id">
     <fieldset class="mb-3"><legend class="fs-6 fw-semibold border-bottom pb-2">Datos personales</legend>
      <div class="mb-2"><label class="form-label" for="nombre">Nombre <span class="text-danger" aria-hidden="true">*</span></label><input class="form-control form-control-sm" name="nombre" id="nombre" maxlength="100" required autocomplete="given-name"></div>
      <div class="row g-2 mb-2">
       <div class="col-sm-6"><label class="form-label" for="apellido_paterno">Apellido paterno <span class="text-danger" aria-hidden="true">*</span></label><input class="form-control form-control-sm" name="apellido_paterno" id="apellido_paterno" maxlength="100" required></div>
       <div class="col-sm-6"><label class="form-label" for="apellido_materno">Apellido materno</label><input class="form-control form-control-sm" name="apellido_materno" id="apellido_materno" maxlength="100"></div>
      </div>
      <div class="mb-2"><label class="form-label" for="rol">Cargo <span class="text-danger" aria-hidden="true">*</span></label><select class="form-select form-select-sm" id="rol" name="rol" required>
       <option value="">Seleccione un cargo</option>
       <?php while($row=$result->fetch_assoc()): ?><option value="<?= (int)$row['rol_id'] ?>"><?= htmlspecialchars($row['rol_nombre'],ENT_QUOTES,'UTF-8') ?></option><?php endwhile ?>
      </select></div>
      <div class="row g-2">
       <div class="col-sm-7"><label class="form-label" for="correo">Correo</label><input class="form-control form-control-sm" type="email" name="correo" id="correo" maxlength="100" autocomplete="email"></div>
       <div class="col-sm-5"><label class="form-label" for="telefono">Teléfono</label><input class="form-control form-control-sm" type="tel" name="telefono" id="telefono" maxlength="20" autocomplete="tel"></div>
      </div>
     </fieldset>
     <fieldset><legend class="fs-6 fw-semibold border-bottom pb-2">Acceso al sistema</legend>
      <div class="mb-2"><label class="form-label" for="usuario">Usuario <span class="text-danger" aria-hidden="true">*</span></label><input class="form-control form-control-sm" name="usuario" id="usuario" required maxlength="50" pattern="\S+" title="El usuario debe ser una sola palabra, sin espacios." autocomplete="off" aria-describedby="usuarioHelp"><small id="usuarioHelp" class="text-body-secondary">Una sola palabra, sin espacios.</small></div>
      <div><label class="form-label" for="clave" id="claveLabel">Contraseña <span class="text-danger" aria-hidden="true">*</span></label><div class="input-group input-group-sm"><input class="form-control" type="password" name="clave" id="clave" maxlength="72" required autocomplete="new-password" aria-describedby="claveHelp"><button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Mostrar contraseña" aria-pressed="false"><i class="fas fa-eye" aria-hidden="true"></i></button></div><small id="claveHelp" class="text-body-secondary">Obligatoria para crear la cuenta.</small></div>
     </fieldset>
    </form>
   </div>
   <div class="modal-footer py-2"><button class="btn btn-sm btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button id="btnGuardarUsuario" type="submit" form="usuarioForm" class="btn btn-sm btn-primary">Guardar usuario</button></div>
  </div>
 </div>
</div>