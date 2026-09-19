<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit();
}

require '../conexion/conexion.php';
date_default_timezone_set('America/Lima');

// ==============================
// 1. CONSULTA: Detalle de uso
// ==============================
$sql = "
SELECT 
  DATE(us.fecha) AS fecha_consulta,
  us.correo AS correo_consultado,
  us.num_urls AS n_codigos,
  CONCAT(
      UCASE(LEFT(pe.nombre,1)), LCASE(SUBSTRING(pe.nombre,2)), ' ',
      UCASE(LEFT(pe.apellido_paterno,1)), LCASE(SUBSTRING(pe.apellido_paterno,2)), ' ',
      UCASE(LEFT(pe.apellido_materno,1)), LCASE(SUBSTRING(pe.apellido_materno,2))
    ) as usuario,
  CASE 
    WHEN us.streaming = '3' THEN 'Netflix (Estoy de Viaje)'
    WHEN us.streaming = '4' THEN 'Disney (Acceso Unico)'
    WHEN us.streaming = '5' THEN 'Netflix (Inicio Session)'
    WHEN us.streaming = '6' THEN 'Soporte'
    ELSE 'N/D'
  END AS servicio
FROM uso_servicio us
LEFT JOIN usuarios usu ON us.usuario = usu.usuario
LEFT JOIN personal pe ON usu.id = pe.usuario_id
WHERE us.streaming IN ('3','4','5','6')
ORDER BY us.fecha DESC
";
$result = $conexion->query($sql);
?>

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
