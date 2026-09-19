
<?php

session_start();

if (!isset($_SESSION['usuario'])) {

  header("Location: login.php");

  exit();
}

require 'conexion/conexion.php';

date_default_timezone_set('America/Lima');

// =====================================
// CONSULTA CORREOS AUTORIZADOS
// =====================================

$sql = "
SELECT
    id,
    correo,
    created_at,
    updated_at
FROM gmail_tokens
ORDER BY updated_at DESC
";

$result =
    $conexion->query($sql);

?>

<div class="contenedor">

  <div class="cardHome tablaHome">

    <!-- HEADER -->
    <div
      style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:15px;
        flex-wrap:wrap;
        gap:10px;
      "
    >

      <h2 style="margin:0;">
        <b>Correos Gmail Autorizados</b>
      </h2>

     
<form
  action="oauth_gmail.php"
  method="POST"
  style="margin:0;"
>


<button
  type="button"
  class="btn-action btn-new btn-soporte"
  onclick="window.location.href='oauth_gmail.php'"
>
  + Autorizar Correo
</button>



</form>



    </div>

    <!-- OPCIONES -->
    <div
      class="tablaOpciones"
      style="
        display:flex;
        justify-content:space-between;
        margin-bottom:10px;
        flex-wrap:wrap;
        gap:10px;
      "
    >

      <!-- BUSCADOR -->
      <input
        type="text"
        id="buscadorGmail"
        placeholder="Buscar correo..."
        style="
          padding:6px 10px;
          border:1px solid #ccc;
          border-radius:6px;
        "
      >

      <!-- REGISTROS -->
      <select
        id="registrosPorPaginaGmail"
        class="selectRegistros"
      >
        <option value="10">
          10 registros
        </option>

        <option value="20" selected>
          20 registros
        </option>

        <option value="50">
          50 registros
        </option>

      </select>

    </div>

    <!-- TABLA -->
    <div class="tablaContainer">

      <table
        class="tabla"
        id="tablaGmail"
      >

        <thead>

          <tr>
            <th>N°</th>
            <th>Correo</th>
            <th>Fecha Registro</th>
            <th>Última Actualización</th>
          </tr>

        </thead>

        <tbody>

          <?php if ($result && $result->num_rows > 0): ?>

            <?php $num = 1; ?>

            <?php while ($row = $result->fetch_assoc()): ?>

              <tr>

                <td>
                  <?= $num ?>
                </td>

                <td>
                  <?= htmlspecialchars($row['correo']) ?>
                </td>

                <td>
                  <?= htmlspecialchars($row['created_at']) ?>
                </td>

                <td>
                  <?= htmlspecialchars($row['updated_at']) ?>
                </td>

              </tr>

              <?php $num++; ?>

            <?php endwhile; ?>

          <?php else: ?>

            <tr>

              <td colspan="4">
                No hay correos autorizados.
              </td>

            </tr>

          <?php endif; ?>

        </tbody>

      </table>

    </div>

    <!-- PAGINACIÓN -->
    <div
      id="paginacionGmail"
      style="
        margin-top:12px;
        display:flex;
        justify-content:flex-end;
        gap:5px;
      "
    ></div>

  </div>

</div>

