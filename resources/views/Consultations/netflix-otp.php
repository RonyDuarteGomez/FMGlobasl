

<div class="tarjetas">

<?php foreach ($lista as $item): ?>

    <div class="tarjeta card card-body">

        <p>
            <strong>Fecha:</strong>
            <?= htmlspecialchars($item["fecha"]) ?>
        </p>

        <?php if (!empty($item["codigo"])): ?>

            <div
                class="codigo-disney codigo-otp"
            >
                <?= htmlspecialchars($item["codigo"]) ?>
            </div>

        <?php else: ?>

            <div class="aviso">
                Código no encontrado.
            </div>

        <?php endif; ?>

        <div class="aviso">
            * Código válido por 15 minutos.
        </div>

    </div>

<?php endforeach; ?>

</div>
