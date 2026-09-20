

<div class="tarjetasSoporte">

    <?php foreach ($lista as $item): ?>

        <div class="tarjetaSoporte card card-body">

            <p>
                <strong>Remitente:</strong>
                <?= htmlspecialchars($item["remitente"]) ?>
            </p>

            <p>
                <strong>Asunto:</strong>
                <?= htmlspecialchars($item["asunto"]) ?>
            </p>

            <p>
                <strong>Fecha:</strong>
                <?= htmlspecialchars($item["fecha"]) ?>
            </p>

            <div class="correo-cuerpo">
                <?= nl2br(htmlspecialchars($item["cuerpo"])) ?>
            </div>

        </div>

    <?php endforeach; ?>

</div>
