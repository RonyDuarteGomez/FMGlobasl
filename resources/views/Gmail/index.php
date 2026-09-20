<div class="contenedor gmail-page">
  <div class="module-heading"><h2>Autorizar Gmail</h2><span class="module-category">Servicios</span></div>
  <div class="cardHome tablaHome card card-body">
    <div class="section-toolbar">
      <form class="sin-margen" action="oauth_gmail.php" method="POST" target="_blank" rel="noopener">
        <?= \FMGlobal\Security\Csrf::field() ?>
        <button type="submit" class="btn btn-sm btn-primary gmail-authorize">Agregar correo <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></button>
      </form>
    </div>
    <div class="tablaOpciones">
      <input class="form-control form-control-sm" type="search" id="buscadorGmail" placeholder="Buscar correo..." aria-label="Buscar correo">
      <select id="estadoTokenGmail" class="form-select form-select-sm" aria-label="Estado del token">
        <option value="">Todos los estados</option><option value="1">Activo</option><option value="0">Inactivo</option>
      </select>
      <select id="registrosPorPaginaGmail" class="selectRegistros form-select form-select-sm" aria-label="Registros por página">
        <option value="10">10 registros</option><option value="20" selected>20 registros</option><option value="50">50 registros</option>
      </select>
    </div>
    <div class="tablaContainer table-responsive">
      <table class="tabla table table-sm table-hover align-middle" id="tablaGmail">
        <thead><tr><th>N°</th><th>Correo</th><th>Token</th><th>Fecha Registro</th><th>Acción</th></tr></thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): $num=1; ?>
          <?php while ($row=$result->fetch_assoc()): $tokenActive=(bool)$row['token_active']; ?>
          <tr data-token-active="<?= $tokenActive ? 1 : 0 ?>">
            <td><?= $num++ ?></td>
            <td><?= htmlspecialchars($row['correo'],ENT_QUOTES,'UTF-8') ?></td>
            <td><span class="badge gmail-token text-white <?= $tokenActive?'bg-success':'bg-danger' ?>"><?= $tokenActive?'Activo':'Inactivo' ?></span></td>
            <td><?= htmlspecialchars(\FMGlobal\Support\DisplayDate::dateTime($row['created_at']),ENT_QUOTES,'UTF-8') ?></td>
            <td><div class="gmail-actions">
              <form class="m-0" action="oauth_gmail.php" method="POST" target="_blank" rel="noopener">
                <?= \FMGlobal\Security\Csrf::field() ?>
                <input type="hidden" name="correo" value="<?= htmlspecialchars($row['correo'],ENT_QUOTES,'UTF-8') ?>">
                <button class="btn btn-sm btn-primary gmail-refresh" type="submit" <?= $tokenActive ? "disabled" : "" ?>>Actualizar token</button>
              </form>
              <form class="m-0" action="mantenimiento_gmail.php" method="POST" data-delete-gmail="<?= htmlspecialchars($row['correo'],ENT_QUOTES,'UTF-8') ?>">
              <?= \FMGlobal\Security\Csrf::field() ?>
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <button type="submit" class="btn btn-danger gmail-delete" title="Eliminar correo" aria-label="Eliminar <?= htmlspecialchars($row['correo'],ENT_QUOTES,'UTF-8') ?>">Eliminar</button>
            </form></div></td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?><tr data-gmail-empty><td colspan="5">No hay correos autorizados.</td></tr><?php endif ?>
        </tbody>
      </table>
    </div>
    <div class="paginacion" id="paginacionGmail"></div>
  </div>
</div>