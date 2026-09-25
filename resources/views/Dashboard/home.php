<?php
$menuPaths = [
 'inicio'=>'M3 10 12 3l9 7M5 9v12h5v-7h4v7h5V9',
 'Gestión'=>'M8 5H5v16h14V5h-3M8 3h8v4H8zM8 12h8M8 16h5',
 'Servicios'=>'M4 7h16v14H4zM8 7V3h8v4M4 12h16M10 12v3h4v-3',
 'Reportes'=>'M4 3v18h17M8 17v-5M13 17V8M18 17V4',
 'Mantenimiento'=>'M14 6a5 5 0 0 0-6 6L3 17l4 4 5-5a5 5 0 0 0 6-6l-3 3-4-4 3-3Z',
 'Activacion'=>'M14 3 5 14h6l-1 7 9-11h-6l1-7Z',
 'Spotify'=>'M9 18V5l11-2v13M9 7l11-2M9 18a3 3 0 1 1-3-3c2 0 3 1 3 3Zm11-2a3 3 0 1 1-3-3c2 0 3 1 3 3Z',
 'Link'=>'m10 13 4-4M8 16l-1 1a4 4 0 0 1-6-6l5-5a4 4 0 0 1 6 0M16 8l1-1a4 4 0 0 1 6 6l-5 5a4 4 0 0 1-6 0',
 'Autoriza'=>'M3 5h18v14H3zM3 5l9 8 9-8',
 'asesorMenu'=>'M8 6a4 4 0 1 0 8 0 4 4 0 1 0-8 0M4 21v-3a8 8 0 0 1 16 0v3M10 14l2 3 2-3M12 17v4',
 'soporteMenu'=>'M4 14v-3a8 8 0 0 1 16 0v3M4 12H2v7h4v-7H4M20 12h2v7h-4v-7h2M20 19v3h-8',
 'reportePublico'=>'M3 21V3h12l5 5v13H3M14 3v6h6M7 13h9M7 17h6',
 'reportesMenu'=>'M4 3v18h17M8 17v-5M13 17V8M18 17V4',
 'menuUsuarios'=>'M6 7a4 4 0 1 0 8 0 4 4 0 1 0-8 0M2 21v-3a8 8 0 0 1 16 0v3M17 3a4 4 0 0 1 0 8M20 14a6 6 0 0 1 2 5v2',
 'menuPermisos'=>'m12 2 9 4v6c0 5-9 10-9 10S3 17 3 12V6l9-4ZM8 12l3 3 5-6',
];
$menuPaths['reporteSoporte']=$menuPaths['soporteMenu'];
$menuPaths['reporteLinks']=$menuPaths['Link'];
$menuPaths['GeneradorExterno']=$menuPaths['Link'];$menuPaths['RestriccionesExterno']=$menuPaths['menuPermisos'];$menuPaths['ReporteExterno']=$menuPaths['reportesMenu'];
$menuPaths['Clientes']=$menuPaths['menuUsuarios'];$menuPaths['VentasSpotify']=$menuPaths['reportesMenu'];
$menuIcon = static fn($name) => '<svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="'.($menuPaths[$name] ?? $menuPaths['Servicios']).'"/></svg>';
?>
<?php $escape=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); ?>
<!DOCTYPE html><html lang="es" data-bs-theme="light" data-lte-color-mode="off"><head>
<meta charset="UTF-8"><base href="<?= $escape(\FMGlobal\Support\CleanUrls::base()) ?>"><script type="application/json" id="cleanRoutes"><?= json_encode(\FMGlobal\Support\CleanUrls::MODULES,JSON_HEX_TAG) ?></script><meta name="csrf-token" content="<?= \FMGlobal\Security\Csrf::token() ?>"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="session-key" content="<?= hash('sha256',session_id()) ?>"><meta name="session-idle" content="<?= \FMGlobal\Security\Session::IDLE_SECONDS ?>"><title>FM Globals · Mi espacio</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/main.css">
<script src="assets/vendor/jquery/jquery.min.js"></script>
</head><body class="fm-panel"><div class="app-wrapper">
<aside class="app-sidebar sidebar shadow" id="sidebar"><div class="sidebar-brand sidebar-header"><span class="full">FM Globals</span><span class="short">FM</span></div>
<nav class="grouped-menu" aria-label="Menú principal">
<button class="nav-item" id="inicio" data-module="dashboard.php" data-init="" title="Inicio" aria-label="Inicio"><?= $menuIcon('inicio') ?><span class="nav-label">Inicio</span></button>
<?php foreach($menuGroups as $group=>$items): ?><details class="nav-group"><summary title="<?= $escape($group) ?>"><?= $menuIcon($group) ?><span class="nav-label"><?= $escape($group) ?></span></summary>
<?php foreach($items as [$id,$label,$url,$permission,$init]): ?><button class="nav-item" id="<?= $id ?>" data-module="<?= $url ?>" data-init="<?= $init ?>" title="<?= $escape($label) ?>" aria-label="<?= $escape($label) ?>"><?= $menuIcon($id) ?><span class="nav-label"><?= $escape($label) ?></span></button><?php endforeach ?>
</details><?php endforeach ?></nav></aside>
<div id="overlay"></div><header class="app-header navbar navbar-expand bg-body">
<button class="toggle-btn btn btn-sm btn-outline-primary" id="toggle-btn" aria-label="Abrir o cerrar menú" aria-expanded="true">☰</button><div class="user-info"><span class="user-name"><?= $escape($nombre) ?></span><form method="POST" action="logout.php"><?= \FMGlobal\Security\Csrf::field() ?><button class="logout-btn btn btn-sm btn-outline-secondary" title="Cerrar sesión" aria-label="Cerrar sesión"><span aria-hidden="true">⏻</span></button></form></div></header>
<main id="contenido" class="app-main"><div id="mantenedorUsuarios" aria-live="polite"></div></main></div>
<script src="assets/js/security.js"></script><script src="assets/js/session.js"></script><script src="assets/js/main.js"></script><script src="assets/js/usuario.js"></script><script src="assets/js/activacion.js"></script><script src="assets/js/asesor.js"></script><script src="assets/js/soporte.js"></script><script src="assets/js/link.js"></script><script src="assets/js/spotify.js"></script><script src="assets/js/clients-sales.js"></script><script src="assets/js/external-links.js"></script>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script><script src="assets/vendor/adminlte/adminlte.min.js"></script><script src="assets/js/table-actions.js"></script></body></html>
