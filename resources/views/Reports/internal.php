

<div class="contenedor">

  <!--
  <div class="cardHome graficoHome">
    <h2><b>Consultas por día</b></h2>
    <canvas id="graficoReportes" height="120"></canvas>
  </div>
  -->

  <div class="cardHome graficoHome">
    <h2><b>Consultas por día</b></h2>
    <canvas id="graficoReportes"></canvas>
  </div>


  <br />

  <div class="cardHome tablaHome">
    <h2><b>Reportes de Consultas</b></h2>

    <!-- OPCIONES -->
    <div class="tablaOpciones" style="display:flex; justify-content:space-between; margin-bottom:10px; flex-wrap:wrap; gap:10px;">

      <!-- Buscador -->
      <input type="text"
        id="buscadorReportes"
        placeholder="Buscar..."
        style="padding:6px 10px; border:1px solid #ccc; border-radius:6px;">

      <!-- Registros -->
      <select id="registrosPorPaginaReportes" class="selectRegistros">
        <option value="10">10 registros</option>
        <option value="20" selected>20 registros</option>
        <option value="50">50 registros</option>
      </select>
    </div>

    <!-- TABLA -->
    <div class="tablaContainer">
      <table class="tabla" id="tablaReportes">
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
                <td><?= htmlspecialchars($row['fecha_consulta']) ?></td>
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
    <div id="paginacionReportes"
      style="margin-top:12px; display:flex; justify-content:flex-end; gap:5px;">
    </div>

  </div>
</div>
