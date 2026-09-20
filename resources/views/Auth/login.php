<!DOCTYPE html>
<html lang="es" data-bs-theme="light" data-lte-color-mode="off">
<head>
 <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>FM Globals · Acceso al sistema</title>
 <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="login-page fm-login">
 <main class="login-box">
  <div class="login-logo"><span>FM</span> Globals</div>
  <div class="card card-outline card-primary shadow-sm">
   <div class="card-header text-center"><h1 class="fs-5 mb-0">Acceso al sistema</h1></div>
   <div class="card-body login-card-body">
    <?php if($mensaje!==''): ?><div class="alert alert-danger py-2 small" role="alert"><?= htmlspecialchars($mensaje,ENT_QUOTES,'UTF-8') ?></div><?php endif ?>
    <form method="POST" action="login.php">
     <?= \FMGlobal\Security\Csrf::field() ?>
     <div class="mb-3"><label for="usuario" class="form-label">Usuario</label>
      <div class="input-group"><input type="text" class="form-control" id="usuario" name="usuario" value="<?= htmlspecialchars($username,ENT_QUOTES,'UTF-8') ?>" placeholder="Ingrese su usuario" autocomplete="username" maxlength="50" pattern="\S+" title="El usuario no debe contener espacios." required autofocus>
       <span class="input-group-text" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg></span></div>
     </div>
     <div class="mb-4"><label for="clave" class="form-label">Contraseña</label>
      <div class="input-group"><input type="password" class="form-control" id="clave" name="clave" placeholder="Ingrese su contraseña" autocomplete="current-password" maxlength="72" required>
       <span class="input-group-text" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V6a4 4 0 0 1 8 0v4M12 14v3"/></svg></span></div>
     </div>
     <button class="btn btn-primary w-100" type="submit">Ingresar</button>
    </form>
   </div>
  </div>
  <p class="login-administrator-note">La administración de cuentas está a cargo del administrador.</p>
 </main>
</body>
</html>