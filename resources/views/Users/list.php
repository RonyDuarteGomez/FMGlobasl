<div class="usuariosOpciones tablaOpciones">
  <input class="form-control form-control-sm" type="search" id="buscadorUsuarios" placeholder="Buscar usuario..." aria-label="Buscar usuario">
  <select class="form-select form-select-sm" id="estadoUsuarios" aria-label="Estado del usuario">
    <option value="">Todos los estados</option><option value="1">Activo</option><option value="0">Inactivo</option>
  </select>
  <select class="form-select form-select-sm" id="registrosUsuarios" aria-label="Registros por página">
    <option value="10">10 registros</option><option value="20" selected>20 registros</option><option value="50">50 registros</option>
  </select>
</div>
<div class="usuarios-container table-responsive">
  <table class="tabla table table-sm table-hover align-middle" id="usuariosTable">
    <thead><tr><th>N°</th><th>Nombre Completo</th><th>Correo</th><th>Teléfono</th><th>Cargo</th><th>Usuario</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php $num=1; while ($row=$result->fetch_assoc()):
      $nombreCompleto=trim($row['nombre'].' '.$row['apellido_paterno'].' '.$row['apellido_materno']);
      $activo=(int)$row['estado']===1;
      $esUsuarioActual=(int)$row['usuario_id']===(int)($_SESSION['usuario_id']??0);
    ?>
      <tr data-estado="<?= $activo?1:0 ?>">
        <td><?= $num++ ?></td>
        <td><?= htmlspecialchars($nombreCompleto) ?></td>
        <td><?= htmlspecialchars($row['correo']) ?></td>
        <td><?= htmlspecialchars($row['telefono']) ?></td>
        <td><?= htmlspecialchars($row['cargo']) ?></td>
        <td><?= htmlspecialchars($row['usuario']) ?></td>
        <td><span class="badge text-white <?= $activo?'bg-success':'bg-danger' ?>"><?= $activo?'Activo':'Inactivo' ?></span></td>
        <td><div class="table-actions">
          <button type="button" class="btn-edit btn btn-sm btn-primary table-action" data-id="<?= (int)$row['usuario_id'] ?>" data-personal="<?= (int)$row['personal_id'] ?>">Editar</button>
          <button type="button" class="<?= $activo?'btn-delete btn-danger':'btn-activate btn-success' ?> btn btn-sm table-action" data-nombre="<?= htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8') ?>" data-id="<?= (int)$row['usuario_id'] ?>" <?= $esUsuarioActual ? 'disabled title="No puedes inactivar tu propia cuenta"' : '' ?>><?= $activo?'Inactivar':'Activar' ?></button>
        </div></td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
<div class="paginacion table-footer" id="paginacionUsuarios"></div>