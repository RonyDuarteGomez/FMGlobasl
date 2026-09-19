<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit();
}
require 'conexion/conexion.php';
date_default_timezone_set('America/Lima');

// ==============================
// 1. CONSULTA: Detalle de uso
// ==============================
$sql = "
SELECT 
  DATE(us.fecha) AS fecha_consulta,
  us.correo AS correo_consultado,
  us.num_urls AS n_codigos,
  CASE WHEN streaming = '1' THEN 'Netflix' ELSE 'Disney'END AS usuario
FROM uso_servicio us
LEFT JOIN usuarios usu ON us.usuario = usu.usuario
LEFT JOIN personal pe ON usu.id = pe.usuario_id
where streaming in ('1','2')
ORDER BY us.fecha DESC;

";
$result = $conexion->query($sql);

// ==============================
// 2. CONSULTA: Totales por día (para gráfico)
// ==============================
$sqlGrafico = "
SELECT 
  DATE(fecha) AS fecha,
  COUNT(*) AS total_consultas
FROM uso_servicio
where streaming in ('1','2')
GROUP BY DATE(fecha)
ORDER BY fecha ASC
";
$resGrafico = $conexion->query($sqlGrafico);

$labels = [];
$valores = [];
while ($row = $resGrafico->fetch_assoc()) {
  $labels[] = $row['fecha'];
  $valores[] = $row['total_consultas'];
}
?>

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