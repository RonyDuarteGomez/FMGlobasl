


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FM GROBALS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/site.css">
</head>

<body>

    <!-- HEADER ORIGINAL -->
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1>FM <span>GROBALS</span></h1>
            </div>
            <nav class="nav">
                <ul>
                    <li><a href="https://fmglobals.com/index.html">Inicio</a></li>
                    <li><a href="https://fmglobals.com/index.html#equipo">Contáctanos</a></li>
                    <li><a href="https://fmglobals.com/index.html#nosotros">Nosotros</a></li>
                    <li><a href="validacion.php">Soporte</a></li>
                </ul>
                <button onclick="window.open('login.php', '_blank');" class="btn-ingresar">Ingresar</button>
            </nav>
            <!-- BOTÓN HAMBURGUESA -->
            <button class="btn-menu" id="btn-menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <main>
        <?php if ($fuera_de_horario): ?>

            <section id="valida" class="container">
                <div class="container-equipo" style="text-align:center;">
                    <h2>⚠️ Servicio deshabilitado temporalmente</h2>
                    <h3>Contacta con tu asesor.</h3>
                </div>
            </section>

        <?php else: ?>

            <?php if (!$modo): ?>

                <!-- ======================
         PANTALLA DE IMÁGENES
    ======================= -->
                <div class="selector-container">

                    <form method="POST" class="selector-opcion">
                        <input type="hidden" name="modo" value="netflix">
                        <button type="submit" style="border:0;background:none;padding:0;width:100%;cursor:pointer;">
                            <img src="assets/img/tarjeta_netflix.png" alt="Netflix">
                        </button>
                    </form>

                    <form method="POST" class="selector-opcion">
                        <input type="hidden" name="modo" value="disney">
                        <button type="submit" style="border:0;background:none;padding:0;width:100%;cursor:pointer;">
                            <img src="assets/img/tarjeta_disney.png" alt="Disney">
                        </button>
                    </form>

                </div>

            <?php else: ?>

                <!-- ======================
         FORMULARIO DINÁMICO
    ======================= -->



                <section id="valida" class="container">

                    <div class="container-equipo">
                        <h2><?= htmlspecialchars($titulo_formulario) ?></h2>
                    </div>

                    <form method="POST">
                        <input type="email" name="correo" placeholder="Ingrese su correo"
                            value="<?= htmlspecialchars($correo ?? '') ?>" required>

                        <button class="btn-ingresar" type="submit">Buscar Código</button>

                        <!-- BOTÓN REGRESAR -->
                        <button class="btn-secundario" type="button"
                            onclick="document.getElementById('volverForm').submit();">
                            Regresar
                        </button>
                    </form>

                    <!-- FORMULARIO OCULTO PARA VOLVER -->
                    <form id="volverForm" method="POST" style="display:none;">
                        <input type="hidden" name="volver" value="1">
                    </form>

                    <div class="tarjetas">

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>

                        <?php elseif ($lista_correos): ?>
                            <?php foreach ($lista_correos as $item): ?>

                                <div class="tarjeta">
                                    <p>
                                        <?= htmlspecialchars($item['nombre']) ?><br>
                                        <?= htmlspecialchars($item['fecha']) ?>
                                    </p>

                                    <?php if ($modo === 'netflix'): ?>

                                        <!-- TARJETA NETFLIX -->
                                        <?php if (!empty($item['url'])): ?>
                                            <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" class="btn-link">
                                                Obtener código
                                            </a>
                                            <div class="aviso">* El enlace vence en 15 minutos.</div>
                                        <?php else: ?>
                                            <div class="alert alert-warning">Código no encontrado en este correo.</div>
                                        <?php endif; ?>

                                    <?php elseif ($modo === 'disney'): ?>

                                        <!-- TARJETA DISNEY+ -->
                                        <?php if (!empty($item['codigo'])): ?>
                                            <div class="codigo-disney">
                                                <?= htmlspecialchars($item['codigo']) ?>
                                            </div>
                                            <div class="aviso">* El código vence en 15 minutos.</div>
                                        <?php else: ?>
                                            <div class="alert alert-warning">No se encontró el código.</div>
                                        <?php endif; ?>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>


                    <!--
                    <div class="tarjetas">

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>

                        <?php elseif ($lista_correos): ?>
                            <?php foreach ($lista_correos as $item): ?>
                                <div class="tarjeta">
                                    <p>
                                        <?= htmlspecialchars($item['nombre']) ?><br>
                                        <?= htmlspecialchars($item['fecha']) ?>
                                    </p>
                                    <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank">Obtener código</a>
                                    <div class="aviso">* El enlace vence en 15 minutos.</div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
                    -->

                </section>

            <?php endif; ?>

        <?php endif; ?>

    </main>

    <footer>
        <div class="footer-text">© 2025 FMgrobals - Todos los derechos reservados</div>
    </footer>
    
    <script src="assets/js/site.js"></script>

</body>

</html>