

<div class="tarjetas">

<?php foreach ($lista as $item): ?>

    <div class="tarjeta">

        <p>
            <strong>Nombre:</strong>
            <?= htmlspecialchars($item["nombre"]) ?>
        </p>

        <p>
            <strong>Fecha:</strong>
            <?= htmlspecialchars($item["fecha"]) ?>
        </p>

        <?php if (!empty($item["url"])): ?>

            <a
                href="<?= htmlspecialchars($item["url"]) ?>"
                class="btn-link"
                target="_blank"
            >
                Obtener código
            </a>

        <?php else: ?>

            <div class="aviso">
                Código no encontrado.
            </div>

        <?php endif; ?>

        <div class="aviso">
            * Enlace válido por 15 minutos.
        </div>

    </div>

<?php endforeach; ?>

</div>