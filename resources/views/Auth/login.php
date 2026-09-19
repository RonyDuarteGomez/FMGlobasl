



<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso al Sistema</title>
  <link rel="stylesheet" href="assets/css/site.css">
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