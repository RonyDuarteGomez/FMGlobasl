<?php
require '../conexion/conexion.php';

// Obtener todas las configuraciones (lunes a domingo)
$query = "SELECT id, dia, hora_inicio, hora_fin FROM activacion where usuario_id = '0' ORDER BY 
    CASE 
        WHEN dia = 'LUNES' THEN 1
        WHEN dia = 'MARTES' THEN 2
        WHEN dia = 'MIERCOLES' THEN 3
        WHEN dia = 'JUEVES' THEN 4
        WHEN dia = 'VIERNES' THEN 5
        WHEN dia = 'SABADO' THEN 6
        WHEN dia = 'DOMINGO' THEN 7
        ELSE 8
    END";
$result = $conexion->query($query);

// Hora y día actual del servidor
date_default_timezone_set('America/Lima');
$hora_actual = (int)date('H');
$dia_actual = strtoupper(date('l')); // Ejemplo: MONDAY
$mapa_dias = [
  'MONDAY' => 'LUNES',
  'TUESDAY' => 'MARTES',
  'WEDNESDAY' => 'MIERCOLES',
  'THURSDAY' => 'JUEVES',
  'FRIDAY' => 'VIERNES',
  'SATURDAY' => 'SABADO',
  'SUNDAY' => 'DOMINGO'
];
$dia_actual = $mapa_dias[$dia_actual] ?? '';

?>

<div class="contenedor-seccion">
  <h2><b>Configuración Activación</b></h2>
  <p>El rango de horas representa el horario en que el servicio estara inactivo.</p>

  <div class="botonera doble">
    <button type="button" class="btn-action btn-new btn-acceder" onclick="window.open('validacion.php', '_blank');">
      Acceder
    </button>
  </div>
  <br />

  <form id="formActivacion" class="form-config">
    <?php while ($row = $result->fetch_assoc()): ?>
      <?php
      $hora_inicio_val = (int)substr($row['hora_inicio'], 0, 2);
      $hora_fin_val = (int)substr($row['hora_fin'], 0, 2);

      // Verificar si es el día actual
      $es_dia_actual = ($row['dia'] === $dia_actual);

      /*echo $row['dia'];
         echo $dia_actual;
         echo $es_dia_actual;
         echo $hora_actual;
         echo $hora_inicio_val;
         echo $hora_fin_val;*/


      // Si es el día actual, verificamos la hora
      if ($es_dia_actual) {
        if ($hora_actual >= $hora_inicio_val && $hora_actual < $hora_fin_val) {
          $activo = false;
        } else {
          $activo = true;
        }
      } else {
        $activo = false;
      }



      $estado_texto = $activo ? '🟢 Activo' : '🔴 Inactivo';
      $estado_color = $activo ? 'color-activo' : 'color-inactivo';
      ?>

      <div class="form-row fila-dia <?= $es_dia_actual ? 'dia-hoy' : '' ?>">
        <div class="form-group dia">
          <label><b><?= htmlspecialchars($row['dia']) ?></b></label>
          <div class="estado <?= $estado_color ?>"><?= $estado_texto ?></div>
        </div>

        <div class="form-group horas">
          <label>Horario</label>
          <div class="grupo-horas">
            <select id="hora_inicio_<?= $row['id'] ?>" name="hora_inicio_<?= $row['id'] ?>">
              <?php
              for ($i = 0; $i < 24; $i++) {
                $hora = str_pad($i, 2, '0', STR_PAD_LEFT);
                $selected = ($hora == $hora_inicio_val) ? 'selected' : '';
                echo "<option value='$hora' $selected>$hora:00</option>";
              }
              ?>
            </select>

            <span class="separador">a</span>

            <select id="hora_fin_<?= $row['id'] ?>" name="hora_fin_<?= $row['id'] ?>">
              <?php
              for ($i = 0; $i < 24; $i++) {
                $hora = str_pad($i, 2, '0', STR_PAD_LEFT);
                $selected = ($hora == $hora_fin_val) ? 'selected' : '';
                echo "<option value='$hora' $selected>$hora:00</option>";
              }
              ?>
            </select>
          </div>
        </div>

        <div class="form-group accion">
          <button
            type="button"
            class="btn-action btn-new btn-actualizar-dia"
            data-id="<?= $row['id'] ?>"
            data-dia="<?= htmlspecialchars($row['dia']) ?>">
            Actualizar
          </button>
        </div>
      </div>
    <?php endwhile; ?>
  </form>


</div>