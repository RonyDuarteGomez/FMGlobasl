<?php
require 'conexion/conexion.php';

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $usuario = trim($_POST["usuario"]);
  $clave   = trim($_POST["clave"]);

  $sqlUsuario = "SELECT 
      u.id, 
      u.usuario,
      u.password_hash,
      u.estado,
      p.nombre,
      p.apellido_paterno,
      p.apellido_materno,
      p.rol_id
    FROM usuarios u
    LEFT JOIN personal p ON u.id = p.usuario_id
    WHERE u.usuario = ?";

  $stmt1 = $conexion->prepare($sqlUsuario);
  $stmt1->bind_param("s", $usuario);
  $stmt1->execute();
  $resultadoUsuario = $stmt1->get_result();

  if ($resultadoUsuario->num_rows === 1) {

    $row = $resultadoUsuario->fetch_assoc();

    if (!password_verify($clave, $row["password_hash"])) {
      $mensaje = "⚠️ Contraseña incorrecta.";
    }
    else if ($row["estado"] != 1) {
      $mensaje = "⚠️ Usuario inactivo.";
    }
    else {

      if ($row["rol_id"] != 1) {

        $sqlHorario = "SELECT 1
            FROM activacion a
            WHERE a.usuario_id = ?
              AND a.dia = ELT(WEEKDAY(CURDATE())+1,'LUNES','MARTES','MIERCOLES','JUEVES','VIERNES','SABADO','DOMINGO')
              AND CURTIME() BETWEEN a.hora_inicio AND a.hora_fin
            LIMIT 1";

        $stmt2 = $conexion->prepare($sqlHorario);
        $stmt2->bind_param("i", $row["id"]);
        $stmt2->execute();
        $resultadoHorario = $stmt2->get_result();

        /*if ($resultadoHorario->num_rows === 0) {
          $mensaje = "⚠️ Usuario fuera del horario permitido.";
        }*/

        $stmt2->close(); // se cierra SOLO aquí
      }

      if (empty($mensaje)) {
        session_start();
        $_SESSION["rol_id"] = $row["rol_id"];
        $_SESSION["usuario_id"] = $row["id"];
        $_SESSION["usuario"] = $row["usuario"];
        $_SESSION["nombre_completo"] = trim($row["nombre"] . ' ' . $row["apellido_paterno"] . ' ' . $row["apellido_materno"]);

        $stmt1->close();
        $conexion->close();

        header("Location: home.php");
        exit;
      }

    }

  } else {
    $mensaje = "⚠️ Usuario no encontrado.";
  }

  // Cerrar al final SOLO UNA VEZ
  $stmt1->close();
}

$conexion->close();

?>



<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso al Sistema</title>
  <link rel="stylesheet" href="styles.css">
</head>

<body>

  <section id="login">
    <div class="container-login">
      <h2>Acceso al Sistema</h2>

      <?php if (!empty($mensaje)): ?>
        <div style="color:red; margin-bottom:10px; font-weight:bold;">
          <?php echo $mensaje; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div style="text-align: left;">
          <label for="usuario">Usuario</label>
          <input type="text" id="usuario" name="usuario" placeholder="Ingrese su usuario" required>
        </div>

        <div style="text-align: left;">
          <label for="clave">Contraseña</label>
          <input type="password" id="clave" name="clave" placeholder="Ingrese su contraseña" required>
        </div>

        <button class="btn-login" type="submit">Acceder</button>
      </form>

      <!--
      <p class="extra-text">
        ¿Olvidaste tu contraseña? <a href="#">Recupérala aquí</a>
      </p>
      -->
    </div>
  </section>

</body>

</html>