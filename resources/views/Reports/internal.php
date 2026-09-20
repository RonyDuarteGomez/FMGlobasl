

<div class="contenedor">
  <div class="module-heading"><h2>Reportes de Consultas</h2><span class="module-category">Reportes</span></div>

  <!--
  <div class="cardHome graficoHome card card-body">
    <h2><b>Consultas por día</b></h2>
    <canvas id="graficoReportes" height="120"></canvas>
  </div>
  -->

  <div class="cardHome graficoHome card card-body">
    <h2><b>Consultas por día</b></h2>
    <canvas id="graficoReportes"></canvas>
  </div>


  <br />

  <div class="cardHome tablaHome card card-body">
    <h2><b>Reportes de Consultas</b></h2>

    <!-- OPCIONES -->
    <div class="tablaOpciones">

      <!-- Buscador -->
      <input class="form-control form-control-sm" type="text"
        id="buscadorReportes"
        placeholder="Buscar...">

      <!-- Registros -->
      <select id="registrosPorPaginaReportes" class="selectRegistros form-select form-select-sm">
        <option value="10">10 registros</option>
        <option value="20" selected>20 registros</option>
        <option value="50">50 registros</option>
      </select>
    </div>

    <!-- TABLA -->
    <div class="tablaContainer table-responsive">
      <table class="tabla table table-sm table-hover align-middle" id="tablaReportes">
        <thead>
          <tr>
            <th>N°</th>
            <th>Fecha</th>
            <th>Correo Consultado</th>
            <th>N° Códigos</th>
            <th>Usuario</th>
            <th>Servicio</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php $num = 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= $num ?></td>
                <td><?= htmlspecialchars(\FMGlobal\Support\DisplayDate::dateTime($row['fecha_consulta'])) ?></td>
                <td><?= htmlspecialchars($row['correo_consultado']) ?></td>
                <td><?= htmlspecialchars($row['n_codigos']) ?></td>
                <td><?= htmlspecialchars($row['usuario']) ?></td>
                <td><?= htmlspecialchars($row['servicio']) ?></td>
              </tr>
              <?php $num++; ?>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5">No se encontraron registros.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- PAGINACIÓN -->
    <div class="paginacion" id="paginacionReportes">
    </div>

  </div>
</div>
