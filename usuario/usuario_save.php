<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    echo "No autorizado";
    exit();
}

require_once "../conexion/conexion.php";

// ============================
// VARIABLES
// ============================
$usuario_id     = !empty($_POST['usuario_id']) ? intval($_POST['usuario_id']) : null;
$nombre         = trim($_POST['nombre']);
$apellido_paterno = trim($_POST['apellido_paterno']);
$apellido_materno = trim($_POST['apellido_materno']);
$correo         = trim($_POST['correo']);
$telefono       = trim($_POST['telefono']);

$clave          = $_POST['clave'];
$rol_id         = !empty($_POST['rol']) ? intval($_POST['rol']) : null;



$conexion->begin_transaction();

try {

    // ======================================================
    // 1️⃣ ACTUALIZAR USUARIO
    // ======================================================
    if ($usuario_id) {

        // EDITAR USUARIO
        if (!empty($clave)) {
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $sql_usuario = "UPDATE usuarios SET password_hash=? WHERE id=?";
            $stmt = $conexion->prepare($sql_usuario);
            $stmt->bind_param("si", $hash, $usuario_id);
            $stmt->execute(); // solo se ejecuta cuando existe
        }


        // EDITAR PERSONAL
        $sql_personal = "UPDATE personal 
                        SET nombre=?, apellido_paterno=?, apellido_materno=?, correo=?, 
                            telefono=?, celular=?, cargo=?, rol_id=? 
                        WHERE usuario_id=?";
        $stmt = $conexion->prepare($sql_personal);
        $stmt->bind_param(
            "sssssssii",
            $nombre,
            $apellido_paterno,
            $apellido_materno,
            $correo,
            $telefono,
            $celular,
            $cargo,
            $rol_id,
            $usuario_id
        );
        $stmt->execute();

        // ======================================================
        // 4️⃣ SI ES EDICIÓN → ACTUALIZAR HORARIOS SOLO SI CAMBIARON
        // ======================================================
        if ($usuario_id && isset($_POST['hora_inicio_1'])) {

            $diasMap = [
                1 => "LUNES",
                2 => "MARTES",
                3 => "MIERCOLES",
                4 => "JUEVES",
                5 => "VIERNES",
                6 => "SABADO",
                7 => "DOMINGO"
            ];

            $sqlUpdate = "UPDATE activacion SET hora_inicio=?, hora_fin=? 
                      WHERE usuario_id=? AND dia=?";
            $stmt = $conexion->prepare($sqlUpdate);

            foreach ($diasMap as $num => $diaTexto) {

                $inicioForm = $_POST["hora_inicio_$num"] ?? "";
                $finForm    = $_POST["hora_fin_$num"] ?? "";

                // Convertir "09" → "09:00:00"
                $inicioFormat = $inicioForm . ":00:00";
                $finFormat    = $finForm . ":00:00";

                // Verificar si cambió (consulta actual)
                $check = $conexion->prepare("SELECT hora_inicio, hora_fin FROM activacion 
                                         WHERE usuario_id=? AND dia=?");
                $check->bind_param("is", $usuario_id, $diaTexto);
                $check->execute();
                $res = $check->get_result()->fetch_assoc();

                if ($res["hora_inicio"] !== $inicioFormat || $res["hora_fin"] !== $finFormat) {
                    // Actualizar solo si cambió
                    $stmt->bind_param("ssis", $inicioFormat, $finFormat, $usuario_id, $diaTexto);
                    $stmt->execute();
                }
            }
        }
    } else {

        $usuario        = trim($_POST['usuario']);

        // ============================
        // VALIDAR USUARIO DUPLICADO
        // ============================
        $check = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $check->bind_param("s", $usuario);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            echo "El usuario ya existe. Ingrese uno nuevo.";
            $conexion->rollback();
            exit();
        }

        // ======================================================
        // 2️⃣ CREAR NUEVO USUARIO
        // ======================================================
        if (empty($clave)) {
            echo "Debe ingresar una contraseña para un nuevo usuario";
            $conexion->rollback();
            exit();
        }

        $hash = password_hash($clave, PASSWORD_DEFAULT);
        $fecha = date("Y-m-d H:i:s");

        $sql_usuario = "INSERT INTO usuarios (usuario, password_hash, estado, fecha_registro) 
                        VALUES (?, ?, 1, ?)";
        $stmt = $conexion->prepare($sql_usuario);
        $stmt->bind_param("sss", $usuario, $hash, $fecha);
        $stmt->execute();

        $usuario_id = $stmt->insert_id;

        // CREAR PERSONAL
        $sql_personal = "INSERT INTO personal 
                        (nombre, apellido_paterno, apellido_materno, correo, telefono, celular, cargo, rol_id, usuario_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql_personal);
        $stmt->bind_param(
            "sssssssii",
            $nombre,
            $apellido_paterno,
            $apellido_materno,
            $correo,
            $telefono,
            $celular,
            $cargo,
            $rol_id,
            $usuario_id
        );
        $stmt->execute();

        // ======================================================
        // 3️⃣ CREAR ACTIVACION – HORARIOS POR DEFECTO
        // ======================================================
        $usuario_registro = $_SESSION['usuario'];

        $dias = [
            ["LUNES",     "09:00:00", "19:00:00"],
            ["MARTES",    "09:00:00", "19:00:00"],
            ["MIERCOLES", "09:00:00", "19:00:00"],
            ["JUEVES",    "09:00:00", "19:00:00"],
            ["VIERNES",   "09:00:00", "19:00:00"],
            ["SABADO",    "09:00:00", "19:00:00"],
            ["DOMINGO",   "00:00:00", "00:00:00"],
        ];

        // Prepara el SQL solo 1 vez (más eficiente)
        $sql_horario = "INSERT INTO activacion 
        (hora_inicio, hora_fin, dia, usuario_registro, fecha_registro, usuario_id)
        VALUES (?, ?, ?, ?, NOW(), ?)";

        $stmt = $conexion->prepare($sql_horario);

        // Realiza 7 inserts
        foreach ($dias as $d) {
            $diaTexto = $d[0];
            $inicio   = $d[1];
            $fin      = $d[2];

            $stmt->bind_param("ssssi", $inicio, $fin, $diaTexto, $usuario_registro, $usuario_id);
            $stmt->execute();
        }
    }



    $conexion->commit();
    echo "Usuario guardado correctamente";
} catch (Exception $e) {
    $conexion->rollback();
    echo "Error: " . $e->getMessage();
}
