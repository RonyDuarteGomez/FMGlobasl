


<div class="usuariosOpciones"
    style="display:flex; justify-content:space-between; margin-bottom:10px; flex-wrap:wrap; gap:10px;">

    <!-- Buscador -->
    <input type="text" id="buscadorUsuarios" placeholder="Buscar..."
        style="padding:6px 10px; border:1px solid #ccc; border-radius:6px;">

    <!-- Registros por página -->
    <select id="registrosUsuarios"
        style="padding:6px 10px; border:1px solid #ccc; border-radius:6px;">
        <option value="10">10 registros</option>
        <option value="20" selected>20 registros</option>
        <option value="50">50 registros</option>
    </select>

</div>

<?php if ($result->num_rows > 0): ?>

    <!--<table id="usuariosTable" class="tbl_usuarios">-->
    <div class="usuarios-container">
        <table class="tabla" id="usuariosTable">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Nombre Completo</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Cargo</th>
                    <th>Usuario</th>
                    <th style="text-align:center;">Estado</th>
                    <th style="text-align:center;">Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php $num = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                    $nombreCompleto = trim(
                        $row['nombre'] . ' ' . $row['apellido_paterno'] . ' ' . $row['apellido_materno']
                    );

                    $estadoText = ($row['estado'] == 1) ? "Activo" : "Inactivo";

                    if ($row['estado'] == 1) {
                        $btnToggleClass = "btn-delete";
                        $btnToggleIcon  = '<i class="fa fa-xmark"></i>';
                        $btnToggleTitle = "Inactivar";
                    } else {
                        $btnToggleClass = "btn-activate";
                        $btnToggleIcon  = '<i class="fa fa-check"></i>';
                        $btnToggleTitle = "Activar";
                    }
                    ?>

                    <tr>
                        <td><?= $num ?></td>
                        <td><?= htmlspecialchars($nombreCompleto) ?></td>
                        <td><?= htmlspecialchars($row['correo']) ?></td>
                        <td><?= htmlspecialchars($row['telefono']) ?></td>
                        <td><?= htmlspecialchars($row['cargo']) ?></td>
                        <td><?= htmlspecialchars($row['usuario']) ?></td>

                        <td style="text-align:center;"><?= $estadoText ?></td>

                        <td style="text-align:center;">
                            <button class="btn-action btn-edit"
                                data-id="<?= $row['usuario_id'] ?>"
                                data-personal="<?= $row['personal_id'] ?>">
                                <i class="fas fa-edit"></i>
                            </button>

                            <button class="btn-action <?= $btnToggleClass ?>"
                                data-id="<?= $row['usuario_id'] ?>"
                                title="<?= $btnToggleTitle ?>">
                                <?= $btnToggleIcon ?>
                            </button>
                        </td>
                    </tr>
                    <?php $num = $num + 1; ?>

                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div id="paginacionUsuarios"
        style="margin-top:12px; display:flex; justify-content:flex-end; gap:5px;">
    </div>

<?php else: ?>

    <p>No hay usuarios registrados.</p>

<?php endif; ?>