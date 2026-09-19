

<div class="tarjetas">

<?php foreach ($lista as $item): ?>

    <div class="tarjeta">

        <p>
            <strong>Fecha:</strong>
            <?= htmlspecialchars($item["fecha"]) ?>
        </p>

        <?php if (!empty($item["codigo"])): ?>

            <div
                class="codigo-disney"
                style="
                    color:#000;
                    padding:10px 15px;
                    border-radius:8px;
                    font-size:1.4rem;
                    font-weight:bold;
                    text-align:center;
                    width:100%;
                    letter-spacing:2px;
                "
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