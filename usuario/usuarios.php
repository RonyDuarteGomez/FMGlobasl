<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require '../conexion/conexion.php';

$sql = "SELECT rol_id, rol_nombre FROM rol";

$result = $conexion->query($sql);



?>

<div id="contenidoUsuarios">
    <h2><b>Mantenimiento de Usuarios</b></h2>
    <button id="btnNuevo" class="btn-action btn-new">Nuevo Usuario</button>
    <div class="tabla-wrapper">
        <div id="tablaUsuarios"></div>
    </div>
</div>

<!-- Modal -->

<div id="modalUsuario" class="ModalGeneral">
    <div class="modal-content-custom">

        <!-- HEADER -->
        <div class="modal-header-custom">
            <h3 id="tituloModalUsuario">Mantenimiento Usuario</h3>
            <span id="cerrarModal">&times;</span>
        </div>

        <!-- BODY: AQUÍ VA TU FORMULARIO -->
        <div class="modal-body-custom">
            <form id="usuarioForm">

                <input type="hidden" name="usuario_id" id="usuario_id">
                <input type="hidden" name="personal_id" id="personal_id">


                <h3>Datos Usuario</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="nombre" id="nombre" required>
                    </div>

                    <div class="form-group">
                        <label>Apellido Paterno</label>
                        <input type="text" name="apellido_paterno" id="apellido_paterno">
                    </div>

                    <div class="form-group">
                        <label>Apellido Materno</label>
                        <input type="text" name="apellido_materno" id="apellido_materno">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label>Cargo</label>
                        <select id="rol" name="rol" required>
                            <option value="">Seleccione un rol</option>
                            <?php
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='{$row['rol_id']}'>{$row['rol_nombre']}</option>";
                            }
                            ?>
                        </select>
                    </div>


                    <div class="form-group" style="flex:1.2;">
                        <label>Correo</label>
                        <input type="email" name="correo" id="correo">
                    </div>

                    <div class="form-group" style="flex:0.8;">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" id="telefono">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label>Usuario</label>
                        <input type="text" name="usuario" id="usuario" required>
                    </div>

                    <div class="form-group" style="flex:1;">
                        <label>Contraseña</label>
                        <div style="position:relative;">
                            <input type="password" name="clave" id="clave" style="width:100%; padding-right:35px;">
                            <i id="togglePassword" class="fas fa-eye"
                                style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer;">
                            </i>
                        </div>
                    </div>
                </div>

                <div id="seccionHorarios">

                    <h3>Horario Servicio</h3>

                    <div class="form-row-usuario">
                        <div class="form-group-usuario horas-usuario">
                            <div class="grupo-horas-usuario">
                                <label>Lunes</label>

                                <select id="hora_inicio_1" name="hora_inicio_1">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                    <?php endfor; ?>
                                </select>

                                <span class="separador-usuario">a</span>

                                <select id="hora_fin_1" name="hora_fin_1">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                    <?php endfor; ?>
                                </select>

                            </div>
                        </div>
                    </div>

                    <div class="form-row-usuario">
                        <div class="form-group-usuario horas-usuario">
                            <div class="grupo-horas-usuario">
                                <label>Martes</label>
                                <select id="hora_inicio_2" name="hora_inicio_2">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                    <?php endfor; ?>
                                </select>

                                <span class="separador-usuario">a</span>

                                <select id="hora_fin_2" name="hora_fin_2">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row-usuario">
                        <div class="form-group-usuario horas-usuario">
                            <div class="grupo-horas-usuario">
                                <label>Miercoles</label>
                                <select id="hora_inicio_3" name="hora_inicio_3">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                    <?php endfor; ?>
                                </select>

                                <span class="separador-usuario">a</span>

                                <select id="hora_fin_3" name="hora_fin_3">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row-usuario">
                        <div class="form-group-usuario horas-usuario">
                            <div class="grupo-horas-usuario">
                                <label>Jueves</label>
                                <select id="hora_inicio_4" name="hora_inicio_4">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                    <?php endfor; ?>
                                </select>

                                <span class="separador-usuario">a</span>

                                <select id="hora_fin_4" name="hora_fin_4">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row-usuario">
                        <div class="form-group-usuario horas-usuario">
                            <div class="grupo-horas-usuario">
                                <label>Viernes</label>
                                <select id="hora_inicio_5" name="hora_inicio_5">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                    <?php endfor; ?>
                                </select>

                                <span class="separador-usuario">a</span>

                                <select id="hora_fin_5" name="hora_fin_5">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row-usuario">
                        <div class="form-group-usuario horas-usuario">
                            <div class="grupo-horas-usuario">
                                <label>Sabado</label>
                                <select id="hora_inicio_6" name="hora_inicio_6">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                    <?php endfor; ?>
                                </select>

                                <span class="separador-usuario">a</span>

                                <select id="hora_fin_6" name="hora_fin_6">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row-usuario">
                        <div class="form-group horas-usuario">
                            <div class="grupo-horas-usuario">
                                <label>Domingo</label>
                                <select id="hora_inicio_7" name="hora_inicio_7">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                    <?php endfor; ?>
                                </select>

                                <span class="separador-usuario">a</span>

                                <select id="hora_fin_7" name="hora_fin_7">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>



            </form>
        </div>

        <!-- FOOTER -->
        <div class="modal-footer-custom">
            <button id="btnGuardarUsuario" type="button" class="btn-action btn-new">Guardar</button>
        </div>

    </div>
</div>