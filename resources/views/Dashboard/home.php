

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FM GROBAL - Panel</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


</head>

<body>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <span class="full">FM GROBAL</span>
      <span class="short">FM</span>
    </div>
    <ul class="menu">

      <?php
      $rol = $_SESSION["rol_id"];

      // ========================
      //  ROL 1 → admin normal
      // ========================
      if ($rol == 1) { ?>

        <li id="inicio"><i class="fa-solid fa-house"></i><span>Inicio</span></li>

        <li id="menuUsuarios"><i class="fa-solid fa-user"></i><span>Usuarios</span></li>

        <li id="Activacion"><i class="fa-solid fa-circle-nodes"></i><span>Cod. Activación</span></li>
        
        <li id="Link"><i class="fa-solid fa-link"></i><span>Link Netflix</span></li>
        
        <li id="Autoriza"><i class="fa-solid fa-at"></i><span>Autorizar Gmail</span></li>

        <li id="asesorMenu"><i class="fa-solid fa-user-tie"></i><span>Asesor</span></li>

        <li id="soporteMenu"><i class="fa-solid fa-headset"></i><span>Soporte</span></li>

        <li id="reportesMenu"><i class="fa-solid fa-chart-pie"></i><span>Reportes</span></li>

      <?php
      }
      // ========================
      //  ROL 2 → Asesor
      // ========================
      else if ($rol == 2) { ?>

        <li id="asesorMenu"><i class="fa-solid fa-user-tie"></i><span>Asesor</span></li>

      <?php
      }
      // ========================
      //  ROL 3 → Soporte
      // ========================
      else if ($rol == 3) { ?>

        <li id="soporteMenu"><i class="fa-solid fa-headset"></i><span>Soporte</span></li>

      <?php } ?>

    </ul>


  </aside>

  <div id="overlay"></div>

  <div class="main">
    <header>
      <button class="toggle-btn" id="toggle-btn">☰</button>
      <div class="user-info">
        <span class="user-name"><?php echo htmlspecialchars($nombre); ?></span>
        <a href="logout.php"><button class="logout-btn" title="Cerrar sesión"><i class="fa-solid fa-power-off"></i></button></a>
      </div>
    </header>

    <div id="contenido">
      <div id="mantenedorUsuarios"></div>
    </div>
  </div>

  <script>
    const rol_id = <?php echo $_SESSION["rol_id"]; ?>;
  </script>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="assets/js/main.js"></script>
  <script src="assets/js/usuario.js"></script>
  <script src="assets/js/activacion.js"></script>
  <script src="assets/js/asesor.js"></script>
  <script src="assets/js/soporte.js"></script>
  <script src="assets/js/link.js"></script>
  <script>window.fmDashboard = <?= json_encode(['labels' => $labels ?? [], 'valores1_clean' => $valores1_clean ?? [], 'valores2_clean' => $valores2_clean ?? [], 'labels_4l' => $labels_4l ?? [], 'valores1_clean_4l' => $valores1_clean_4l ?? [], 'valores2_clean_4l' => $valores2_clean_4l ?? [], 'valores3_clean_4l' => $valores3_clean_4l ?? [], 'valores4_clean_4l' => $valores4_clean_4l ?? []], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
  <script src="assets/js/dashboard.js"></script>

</body>

</html>