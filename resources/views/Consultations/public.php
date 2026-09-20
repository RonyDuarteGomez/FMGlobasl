<?php $escape=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); ?>
<!DOCTYPE html><html lang="es" data-bs-theme="light" data-lte-color-mode="off">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>FM Globals · Soporte clientes</title>
<link rel="stylesheet" href="assets/css/site.css"><link rel="stylesheet" href="assets/css/landing.css"><link rel="stylesheet" href="assets/css/support-public.css"></head>
<body class="fm-website public-support">
<a class="web-skip" href="#soporte-clientes">Saltar al contenido</a>
<header class="web-header"><div class="web-container web-nav-row">
 <a class="web-brand" href="index.html" aria-label="FM Globals, inicio"><span class="web-brand-mark">FM</span> Globals</a>
 <button class="web-menu-toggle btn btn-sm btn-outline-primary" type="button" aria-controls="web-nav" aria-expanded="false" aria-label="Abrir menú">☰</button>
 <nav id="web-nav" aria-label="Navegación principal">
  <a href="index.html#servicios">Servicios</a><a href="index.html#equipo">Contáctanos</a><a href="index.html#nosotros">Nosotros</a><a href="validacion.php">Soporte clientes</a><a class="btn btn-sm btn-primary" href="ingresar" target="_blank" rel="noopener noreferrer">Ingresar</a>
 </nav>
</div></header>
<main id="soporte-clientes" class="web-container support-main">
 <div class="support-intro"><p class="web-eyebrow">ESTAMOS PARA AYUDARTE</p><h1>Soporte para clientes</h1><p>Consulta el código de acceso de tu servicio en unos pasos.</p></div>
 <?php if($fuera_de_horario): ?>
 <section class="support-closed card card-body text-center"><span class="support-symbol" aria-hidden="true">◷</span><h2>Servicio deshabilitado temporalmente</h2><p>Contacta con tu asesor para recibir ayuda.</p><a class="btn btn-primary" href="index.html#equipo">Contactar asesor</a><a href="index.html" class="support-back">Volver al inicio</a></section>
 <?php elseif(!$modo): ?>
 <div class="support-step"><span>1</span> Elige el servicio que necesitas</div>
 <div class="support-options">
 <?php foreach(['netflix'=>['Netflix','tarjeta_netflix.png'],'disney'=>['Disney+','tarjeta_disney.png']] as $service=>[$label,$picture]): ?>
 <form method="POST" action="validacion.php" class="support-option card">
 <?= \FMGlobal\Security\Csrf::field() ?><input type="hidden" name="modo" value="<?= $service ?>">
 <button type="submit"><img src="assets/img/<?= $picture ?>" alt="<?= $label ?>"><span class="support-option-action">Consultar acceso <?= $label ?><span aria-hidden="true">→</span></span></button>
 </form>
 <?php endforeach ?>
 </div>
 <?php else: ?>
 <div class="support-workspace">
  <section class="card card-body support-query">
   <div class="support-selected"><img src="assets/img/<?= $modo==='netflix'?'netflix.png':'disney.png' ?>" alt="" width="48" height="48"><div><span class="small text-body-secondary">Servicio seleccionado</span><h2><?= $modo==='netflix'?'Netflix':'Disney+' ?></h2></div></div>
   <div class="support-step"><span>2</span> Ingresa el correo de tu cuenta</div>
   <form method="POST" action="validacion.php" id="supportSearch">
    <?= \FMGlobal\Security\Csrf::field() ?>
    <label for="supportEmail" class="form-label">Correo electrónico <span class="text-danger" aria-hidden="true">*</span></label>
    <input class="form-control" type="email" id="supportEmail" name="correo" maxlength="254" autocomplete="email" placeholder="nombre@correo.com" value="<?= $escape($correo) ?>" required aria-describedby="supportEmailHelp">
    <p id="supportEmailHelp" class="form-text">Usa el correo asociado a tu servicio.</p>
    <button class="btn btn-primary w-100" type="submit">Buscar código <span aria-hidden="true">→</span></button>
   </form>
   <form method="POST" action="validacion.php" class="support-return"><?= \FMGlobal\Security\Csrf::field() ?><input type="hidden" name="volver" value="1"><button class="btn btn-link" type="submit">← Cambiar servicio</button></form>
  </section>
  <section class="support-results" aria-labelledby="supportResultsTitle" aria-live="polite">
   <h2 id="supportResultsTitle">Resultado de tu consulta</h2>
   <?php if($error): ?><div class="alert alert-warning" role="alert"><?= $escape($error) ?></div><p class="small text-body-secondary">Revisa el correo ingresado o contacta con tu asesor si necesitas ayuda.</p>
   <?php elseif($lista_correos): foreach($lista_correos as $item): ?>
    <article class="card card-body support-result"><div class="d-flex justify-content-between align-items-start gap-2"><h3><?= $escape($item['nombre']?:($modo==='netflix'?'Netflix':'Disney+')) ?></h3><span class="badge text-bg-success">Encontrado</span></div><p class="small text-body-secondary"><?= $escape($item['fecha']) ?></p>
    <?php if($modo==='netflix' && !empty($item['url'])): ?><a class="btn btn-primary" href="<?= $escape($item['url']) ?>" target="_blank" rel="noopener noreferrer">Obtener código ↗</a><p class="support-expiry">El enlace vence en 15 minutos.</p>
    <?php elseif($modo==='disney' && !empty($item['codigo'])): ?><div class="support-code" aria-label="Código de acceso"><?= $escape($item['codigo']) ?></div><p class="support-expiry">El código vence en 15 minutos.</p>
    <?php else: ?><p class="alert alert-warning mb-0">No se encontró el código en este correo.</p><?php endif ?>
    </article>
   <?php endforeach; else: ?><div class="support-empty"><span class="support-symbol" aria-hidden="true">✉</span><h3>Tu código aparecerá aquí</h3><p>Ingresa tu correo y selecciona «Buscar código».</p></div><?php endif ?>
  </section>
 </div>
 <?php endif ?>
 <div class="support-help"><span>¿Necesitas ayuda adicional?</span><a href="index.html#equipo">Hablar con un asesor ↗</a></div>
</main>
<footer class="web-footer"><div class="web-container"><a class="web-brand" href="index.html"><span class="web-brand-mark">FM</span> Globals</a><small>© <span id="web-year">2026</span> FM Globals. Todos los derechos reservados.</small></div></footer>
<div id="supportLoading" class="support-loading" hidden role="status" aria-live="polite"><div class="card card-body text-center"><div class="spinner-border text-primary mx-auto mb-3" aria-hidden="true"></div><strong>Buscando…</strong><span class="small text-body-secondary mt-2">Estamos consultando tu servicio.</span></div></div>
<script src="assets/js/site.js"></script><script src="assets/js/support-public.js"></script>
</body></html>