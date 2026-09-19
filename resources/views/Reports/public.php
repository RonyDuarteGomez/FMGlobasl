

<div class="contenedor">

  <div class="cardHome graficoHome">
    <h2><b>Consultas por día</b></h2>
    <canvas id="graficoConsultas" height="120"></canvas>
  </div>
  <br />

  <div class="cardHome tablaHome">
    <h2><b>Detalle de Consultas</b></h2>
    <!-- 🧾 TABLA -->
    <div class="tablaOpciones" style="display:flex; justify-content:space-between; margin-bottom:10px; flex-wrap:wrap; gap:10px;">

      <!-- Buscador -->
      <input type="text" id="buscadorTabla" placeholder="Buscar..."
        style="padding:6px 10px; border:1px solid #ccc; border-radius:6px;">

      <!-- Registros por página -->
      <div>
        <select id="registrosPorPagina" class="selectRegistros">
          <option value="10">10 registros</option>
          <option value="20" selected>20 registros</option>
          <option value="50">50 registros</option>
        </select>

      </div>
    </div>

    <div class="tablaContainer">
      <table class="tabla" id="tablaConsultas">
        <thead>
          <tr>
            <th>N°</th>
            <th>Fecha</th>
            <th>Correo Consultado</th>
            <th>N° Códigos</th>
            <th>Servicio</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php $num = 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= $num ?></td>
                <td><?= htmlspecialchars($row['fecha_consulta']) ?></td>
                <td><?= htmlspecialchars($row['correo_consultado']) ?></td>
                <td><?= htmlspecialchars($row['n_codigos']) ?></td>
                <td><?= htmlspecialchars($row['usuario']) ?></td>
              </tr>
              <?php $num = $num + 1; ?>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4">No se encontraron registros.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div id="paginacionTabla" style="margin-top:12px; display:flex; justify-content:flex-end; gap:5px;"></div>
</div>