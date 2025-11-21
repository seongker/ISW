<?php
session_start();

// Verificar si el usuario ha iniciado sesión y es un trabajador
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'trabajador') {
    header("Location: index.php");
    exit();
}

// Incluir archivo de conexión
require_once 'Conexion/conexion.php';

// Inicializar variables
$mensaje = "";
$error = "";
$datos_trabajador = null;
$datos_laboratorio = null;
$datos_insumo = null;
$modo_edicion = false;
$modo_edicion_lab = false;
$modo_edicion_insumo = false;
$pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'dashboard';

// Procesar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Determinar si estamos editando un trabajador existente
$id_edicion = isset($_GET['editar']) ? (int)$_GET['editar'] : 0;

// Obtener datos del trabajador para edición
if ($id_edicion > 0) {
    $sql = "SELECT p.*, u.NUM_USUARIO, u.PIN
            FROM personas p 
            INNER JOIN usuarios u ON p.idPERSONAS = u.PERSONAS_idPERSONAS 
            WHERE p.idPERSONAS = $id_edicion";
    $resultado = $conexion->query($sql);
    
    if ($resultado->num_rows > 0) {
        $datos_trabajador = $resultado->fetch_assoc();
        $modo_edicion = true;
        $pagina_actual = 'gestion';
    } else {
        $error = "Trabajador no encontrado.";
    }
}

// Determinar si estamos editando un laboratorio existente
$id_edicion_lab = isset($_GET['editar_lab']) ? (int)$_GET['editar_lab'] : 0;

// Obtener datos del laboratorio para edición
if ($id_edicion_lab > 0) {
    $sql = "SELECT l.*, p.NOMBRE, p.PATERNO, p.MATERNO 
            FROM laboratorios l 
            INNER JOIN personas p ON l.PERSONAS_idPERSONAS = p.idPERSONAS 
            WHERE l.idLABORATORIOS = $id_edicion_lab";
    $resultado = $conexion->query($sql);
    
    if ($resultado->num_rows > 0) {
        $datos_laboratorio = $resultado->fetch_assoc();
        $modo_edicion_lab = true;
        $pagina_actual = 'laboratorios';
    } else {
        $error = "Laboratorio no encontrado.";
    }
}

// Determinar si estamos editando un insumo existente
$id_edicion_insumo = isset($_GET['editar_insumo']) ? (int)$_GET['editar_insumo'] : 0;

// Obtener datos del insumo para edición
if ($id_edicion_insumo > 0) {
    $sql = "SELECT i.*, l.NOM_LAB 
            FROM insumos i 
            INNER JOIN laboratorios l ON i.LABORATORIOS_idLABORATORIOS = l.idLABORATORIOS 
            WHERE i.idINSUMOS = $id_edicion_insumo";
    $resultado = $conexion->query($sql);
    
    if ($resultado->num_rows > 0) {
        $datos_insumo = $resultado->fetch_assoc();
        $modo_edicion_insumo = true;
        $pagina_actual = 'insumos';
    } else {
        $error = "Insumo no encontrado.";
    }
}

// Procesar formulario de registro/edición de trabajadores
if ($_SERVER["REQUEST_METHOD"] == "POST" && (!isset($_POST['form_type']) || $_POST['form_type'] == 'trabajador')) {
    // Limpiar y validar datos
    $nombre = limpiarDatos($_POST['nombre'], $conexion);
    $paterno = limpiarDatos($_POST['paterno'], $conexion);
    $materno = limpiarDatos($_POST['materno'], $conexion);
    $correo = limpiarDatos($_POST['correo'], $conexion);
    $telefono = limpiarDatos($_POST['telefono'], $conexion);
    $numero_control = limpiarDatos($_POST['numero_control'], $conexion);
    $pin = limpiarDatos($_POST['pin'], $conexion);
    $rol = isset($_POST['rol']) ? limpiarDatos($_POST['rol'], $conexion) : 'trabajador';
    $estatus = isset($_POST['estatus']) ? (int)$_POST['estatus'] : 1;

    // Obtener ID si estamos en modo edición
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // === VALIDACIONES BÁSICAS ===
    if (empty($nombre) || empty($paterno) || empty($numero_control) || empty($pin) || empty($correo) || empty($telefono)) {
        $error = "Por favor, complete todos los campos obligatorios.";
    } elseif (strlen($pin) != 4 || !is_numeric($pin)) {
        $error = "El PIN debe tener exactamente 4 dígitos numéricos.";
    } elseif (!is_numeric($numero_control)) {
        $error = "El número de control solo debe contener números.";
    } elseif (!is_numeric($pin)) {
        $error = "El PIN solo debe contener números.";
    } elseif (!preg_match('/^[0-9]{10}$/', $telefono)) {
        $error = "El número de teléfono debe contener exactamente 10 dígitos.";
    } elseif (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/', $nombre)) {
        $error = "El nombre solo puede contener letras y espacios.";
    } elseif (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/', $paterno)) {
        $error = "El apellido paterno solo puede contener letras y espacios.";
    } elseif (!empty($materno) && !preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/', $materno)) {
        $error = "El apellido materno solo puede contener letras y espacios.";
    } elseif (!preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.com$/', $correo)) {
        $error = "El correo debe ser válido y terminar en .com";
    } else {
        // === VALIDAR SI EL CORREO YA EXISTE ===
        if ($id > 0) {
            $sql_check = "SELECT COUNT(*) AS total FROM personas WHERE CORREO = ? AND idPERSONAS != ?";
            $stmt_check = $conexion->prepare($sql_check);
            $stmt_check->bind_param("si", $correo, $id);
        } else {
            $sql_check = "SELECT COUNT(*) AS total FROM personas WHERE CORREO = ?";
            $stmt_check = $conexion->prepare($sql_check);
            $stmt_check->bind_param("s", $correo);
        }
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row_check = $result_check->fetch_assoc();

        if ($row_check['total'] > 0) {
            $error = "Este correo ya ha sido registrado.";
        } else {
            // VALIDACIÓN: Número de control único (excepto para el registro actual en edición)
            if ($id > 0) {
                $sql_check_numero = "SELECT COUNT(*) as total FROM usuarios WHERE NUM_USUARIO = ? AND PERSONAS_idPERSONAS != ?";
                $stmt_num = $conexion->prepare($sql_check_numero);
                $stmt_num->bind_param("si", $numero_control, $id);
            } else {
                $sql_check_numero = "SELECT COUNT(*) as total FROM usuarios WHERE NUM_USUARIO = ?";
                $stmt_num = $conexion->prepare($sql_check_numero);
                $stmt_num->bind_param("s", $numero_control);
            }
            $stmt_num->execute();
            $result_numero = $stmt_num->get_result();
            $existe_numero = $result_numero->fetch_assoc()['total'] > 0;

            // VALIDACIÓN: Teléfono único (excepto para el registro actual en edición)
            if ($id > 0) {
                $sql_check_telefono = "SELECT COUNT(*) as total FROM personas WHERE TELEFONO = ? AND TELEFONO != '' AND idPERSONAS != ?";
                $stmt_tel = $conexion->prepare($sql_check_telefono);
                $stmt_tel->bind_param("si", $telefono, $id);
            } else {
                $sql_check_telefono = "SELECT COUNT(*) as total FROM personas WHERE TELEFONO = ? AND TELEFONO != ''";
                $stmt_tel = $conexion->prepare($sql_check_telefono);
                $stmt_tel->bind_param("s", $telefono);
            }
            $stmt_tel->execute();
            $result_telefono = $stmt_tel->get_result();
            $existe_telefono = $result_telefono->fetch_assoc()['total'] > 0;

            if ($existe_numero) {
                $error = "El número de control ya está registrado en el sistema.";
            } elseif ($existe_telefono) {
                $error = "El número de teléfono ya está registrado en el sistema.";
            } else {
                // Iniciar transacción
                $conexion->begin_transaction();

                try {
                    if ($id > 0) {
                        // MODO EDICIÓN: Actualizar registro existente
                        $sql_persona = "UPDATE personas SET 
                                        NOMBRE = ?, 
                                        PATERNO = ?, 
                                        MATERNO = ?, 
                                        CORREO = ?, 
                                        TELEFONO = ?, 
                                        ROL = ?,
                                        ESTATUS = ?
                                        WHERE idPERSONAS = ?";
                        $stmt_persona = $conexion->prepare($sql_persona);
                        $stmt_persona->bind_param("ssssssii", $nombre, $paterno, $materno, $correo, $telefono, $rol, $estatus, $id);

                        if ($stmt_persona->execute() === FALSE) {
                            throw new Exception("Error al actualizar persona: " . $stmt_persona->error);
                        }

                        // SOLO ACTUALIZAR DATOS BÁSICOS EN USUARIOS (SIN ESTATUS)
                        $sql_usuario = "UPDATE usuarios SET 
                                        NUM_USUARIO = ?, 
                                        PIN = ?
                                        WHERE PERSONAS_idPERSONAS = ?";
                        $stmt_usuario = $conexion->prepare($sql_usuario);
                        $stmt_usuario->bind_param("ssi", $numero_control, $pin, $id);

                        if ($stmt_usuario->execute() === FALSE) {
                            throw new Exception("Error al actualizar usuario: " . $stmt_usuario->error);
                        }

                        $conexion->commit();
                        $mensaje = "Trabajador actualizado exitosamente.";
                        $modo_edicion = false;
                    } else {
                        // MODO REGISTRO: Insertar nuevo registro
                        $sql_persona = "INSERT INTO personas (NOMBRE, PATERNO, MATERNO, CORREO, TELEFONO, ROL, ESTATUS) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt_persona = $conexion->prepare($sql_persona);
                        $stmt_persona->bind_param("ssssssi", $nombre, $paterno, $materno, $correo, $telefono, $rol, $estatus);

                        if ($stmt_persona->execute() === FALSE) {
                            throw new Exception("Error al registrar persona: " . $stmt_persona->error);
                        }

                        $id_persona = $conexion->insert_id;

                        // SOLO INSERTAR DATOS BÁSICOS EN USUARIOS (SIN ESTATUS)
                        $sql_usuario = "INSERT INTO usuarios (NUM_USUARIO, PIN, FECHA_CREACION, PERSONAS_idPERSONAS) 
                                        VALUES (?, ?, NOW(), ?)";
                        $stmt_usuario = $conexion->prepare($sql_usuario);
                        $stmt_usuario->bind_param("ssi", $numero_control, $pin, $id_persona);

                        if ($stmt_usuario->execute() === FALSE) {
                            throw new Exception("Error al registrar usuario: " . $stmt_usuario->error);
                        }

                        $conexion->commit();
                        $mensaje = "Trabajador registrado exitosamente.";
                    }
                } catch (Exception $e) {
                    $conexion->rollback();
                    $error = "Error en el proceso: " . $e->getMessage();
                }
            }
        }
    }
}

// Procesar // Procesar formulario de registro/edición de laboratorios
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] == 'laboratorio') {
    // Limpiar y validar datos
    $nombre_lab = limpiarDatos($_POST['nombre_lab'], $conexion);
    $horario = limpiarDatos($_POST['horario'], $conexion);
    $ubicacion = limpiarDatos($_POST['ubicacion'], $conexion);
    $persona_id = (int)$_POST['persona_id'];
    
    // El encargado se asigna automáticamente con el usuario en sesión
    $encargado_lab = $_SESSION['nombre'];
    
    // Obtener ID si estamos en modo edición
    $id_lab = isset($_POST['id_lab']) ? (int)$_POST['id_lab'] : 0;
    
    // Validaciones básicas
    if (empty($nombre_lab) || empty($ubicacion) || $persona_id == 0) {
        $error = "Por favor, complete todos los campos obligatorios.";
        // MANTENER MODO EDICIÓN SI HAY ERROR
        if ($id_lab > 0) {
            $modo_edicion_lab = true;
            $pagina_actual = 'laboratorios';
        }
    } else {
        // Validar nombre del laboratorio (solo letras y espacios, máximo 50 caracteres)
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,50}$/', $nombre_lab)) {
            $error = "El nombre del laboratorio solo puede contener letras y espacios, máximo 50 caracteres.";
            // MANTENER MODO EDICIÓN SI HAY ERROR
            if ($id_lab > 0) {
                $modo_edicion_lab = true;
                $pagina_actual = 'laboratorios';
            }
        }
        // Validar ubicación (solo números y letras, máximo 30 caracteres)
        elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]{1,30}$/', $ubicacion)) {
            $error = "La ubicación solo puede contener letras y números, máximo 30 caracteres.";
            // MANTENER MODO EDICIÓN SI HAY ERROR
            if ($id_lab > 0) {
                $modo_edicion_lab = true;
                $pagina_actual = 'laboratorios';
            }
        }
        else {
            // VALIDAR QUE EL NOMBRE DEL LABORATORIO NO SE REPITA (excepto en edición)
            if ($id_lab > 0) {
                $sql_check_nombre = "SELECT COUNT(*) as total FROM laboratorios 
                                    WHERE NOM_LAB = '$nombre_lab' 
                                    AND idLABORATORIOS != $id_lab";
            } else {
                $sql_check_nombre = "SELECT COUNT(*) as total FROM laboratorios 
                                    WHERE NOM_LAB = '$nombre_lab'";
            }
            
            $result_nombre = $conexion->query($sql_check_nombre);
            $existe_nombre = $result_nombre->fetch_assoc()['total'] > 0;
            
            if ($existe_nombre) {
                $error = "Ya existe un laboratorio con el nombre '$nombre_lab'. Por favor, use un nombre diferente.";
                // MANTENER MODO EDICIÓN SI HAY ERROR
                if ($id_lab > 0) {
                    $modo_edicion_lab = true;
                    $pagina_actual = 'laboratorios';
                }
            } else {
                if ($id_lab > 0) {
                    // MODO EDICIÓN: Actualizar laboratorio existente
                    $sql_lab = "UPDATE laboratorios SET 
                                NOM_LAB = '$nombre_lab', 
                                ENCARGADO_LAB = '$encargado_lab', 
                                HORARIO = '$horario', 
                                UBICACION = '$ubicacion',
                                PERSONAS_idPERSONAS = $persona_id 
                                WHERE idLABORATORIOS = $id_lab";
                    
                    if ($conexion->query($sql_lab) === TRUE) {
                        $mensaje = "Laboratorio actualizado exitosamente.";
                        $modo_edicion_lab = false;
                    } else {
                        $error = "Error al actualizar laboratorio: " . $conexion->error;
                        // MANTENER MODO EDICIÓN SI HAY ERROR EN LA CONSULTA
                        $modo_edicion_lab = true;
                        $pagina_actual = 'laboratorios';
                    }
                } else {
                    // MODO REGISTRO: Insertar nuevo laboratorio
                    $sql_lab = "INSERT INTO laboratorios (NOM_LAB, ENCARGADO_LAB, HORARIO, UBICACION, PERSONAS_idPERSONAS) 
                                VALUES ('$nombre_lab', '$encargado_lab', '$horario', '$ubicacion', $persona_id)";
                    
                    if ($conexion->query($sql_lab) === TRUE) {
                        $mensaje = "Laboratorio registrado exitosamente.";
                    } else {
                        $error = "Error al registrar laboratorio: " . $conexion->error;
                    }
                }
            }
        }
    }
}

// Si hay error y estamos en modo edición, recargar los datos del laboratorio
if (!empty($error) && $id_lab > 0 && $modo_edicion_lab) {
    $sql = "SELECT l.*, p.NOMBRE, p.PATERNO, p.MATERNO 
            FROM laboratorios l 
            INNER JOIN personas p ON l.PERSONAS_idPERSONAS = p.idPERSONAS 
            WHERE l.idLABORATORIOS = $id_lab";
    $resultado = $conexion->query($sql);
    
    if ($resultado->num_rows > 0) {
        $datos_laboratorio = $resultado->fetch_assoc();
    }
}

// Procesar formulario de registro/edición de insumos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] == 'insumo') {
    // Limpiar y validar datos
    $nombre_insumo = limpiarDatos($_POST['nombre_insumo'], $conexion);
    $descripcion = limpiarDatos($_POST['descripcion'], $conexion);
    $codigo_barras = limpiarDatos($_POST['codigo_barras'], $conexion);
    $cantidad = (int)$_POST['cantidad'];
    $laboratorio_id = (int)$_POST['laboratorio_id'];
    
    // Obtener ID si estamos en modo edición
    $id_insumo = isset($_POST['id_insumo']) ? (int)$_POST['id_insumo'] : 0;
    
    // Validaciones básicas
    if (empty($nombre_insumo) || empty($descripcion) || empty($codigo_barras) || $cantidad < 0 || $laboratorio_id == 0) {
        $error = "Por favor, complete todos los campos obligatorios con datos válidos.";
        // MANTENER MODO EDICIÓN SI HAY ERROR
        if ($id_insumo > 0) {
            $modo_edicion_insumo = true;
            $pagina_actual = 'insumos';
        }
    } else {
        // Validar nombre del insumo (solo letras y espacios, máximo 30 caracteres)
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,30}$/', $nombre_insumo)) {
            $error = "El nombre del insumo solo puede contener letras y espacios, máximo 30 caracteres.";
            // MANTENER MODO EDICIÓN SI HAY ERROR
            if ($id_insumo > 0) {
                $modo_edicion_insumo = true;
                $pagina_actual = 'insumos';
            }
        }
        // Validar código de barras (solo números, máximo 50 caracteres)
        elseif (!preg_match('/^[0-9]{1,50}$/', $codigo_barras)) {
            $error = "El código de barras solo puede contener números, máximo 50 caracteres.";
            // MANTENER MODO EDICIÓN SI HAY ERROR
            if ($id_insumo > 0) {
                $modo_edicion_insumo = true;
                $pagina_actual = 'insumos';
            }
        }
        // Validar cantidad (solo números, máximo 3 dígitos)
        elseif (!preg_match('/^[0-9]{1,3}$/', $cantidad) || $cantidad > 999) {
            $error = "La cantidad debe ser un número entre 0 y 999.";
            // MANTENER MODO EDICIÓN SI HAY ERROR
            if ($id_insumo > 0) {
                $modo_edicion_insumo = true;
                $pagina_actual = 'insumos';
            }
        }
        else {
            // VALIDAR QUE EL CÓDIGO DE BARRAS SEA ÚNICO (excepto en edición)
            if ($id_insumo > 0) {
                $sql_check_codigo = "SELECT COUNT(*) as total FROM insumos WHERE CODIGO_BARRAS = '$codigo_barras' AND idINSUMOS != $id_insumo AND ESTATUS = 1";
            } else {
                $sql_check_codigo = "SELECT COUNT(*) as total FROM insumos WHERE CODIGO_BARRAS = '$codigo_barras' AND ESTATUS = 1";
            }
            $result_codigo = $conexion->query($sql_check_codigo);
            $existe_codigo = $result_codigo->fetch_assoc()['total'] > 0;
            
            if ($existe_codigo) {
                $error = "El código de barras ya está registrado en el sistema.";
                // MANTENER MODO EDICIÓN SI HAY ERROR
                if ($id_insumo > 0) {
                    $modo_edicion_insumo = true;
                    $pagina_actual = 'insumos';
                }
            } else {
                // NUEVA VALIDACIÓN: Verificar que el nombre no se repita en el mismo laboratorio
                if ($id_insumo > 0) {
                    $sql_check_nombre = "SELECT COUNT(*) as total FROM insumos 
                                        WHERE NOMBRE = '$nombre_insumo' 
                                        AND LABORATORIOS_idLABORATORIOS = $laboratorio_id 
                                        AND idINSUMOS != $id_insumo 
                                        AND ESTATUS = 1";
                } else {
                    $sql_check_nombre = "SELECT COUNT(*) as total FROM insumos 
                                        WHERE NOMBRE = '$nombre_insumo' 
                                        AND LABORATORIOS_idLABORATORIOS = $laboratorio_id 
                                        AND ESTATUS = 1";
                }
                
                $result_nombre = $conexion->query($sql_check_nombre);
                $existe_nombre = $result_nombre->fetch_assoc()['total'] > 0;
                
                if ($existe_nombre) {
                    // Obtener el nombre del laboratorio para el mensaje de error
                    $sql_lab_nombre = "SELECT NOM_LAB FROM laboratorios WHERE idLABORATORIOS = $laboratorio_id";
                    $result_lab = $conexion->query($sql_lab_nombre);
                    $lab_nombre = $result_lab->fetch_assoc()['NOM_LAB'];
                    
                    $error = "Ya existe un insumo con el nombre '$nombre_insumo' en el laboratorio '$lab_nombre'. Por favor, use un nombre diferente para este laboratorio.";
                    // MANTENER MODO EDICIÓN SI HAY ERROR
                    if ($id_insumo > 0) {
                        $modo_edicion_insumo = true;
                        $pagina_actual = 'insumos';
                    }
                }
            }
            
            if (empty($error)) {
                if ($id_insumo > 0) {
                    // MODO EDICIÓN: Actualizar insumo existente
                    $sql_insumo = "UPDATE insumos SET 
                                  NOMBRE = '$nombre_insumo', 
                                  DESCRIPCION = '$descripcion', 
                                  CODIGO_BARRAS = '$codigo_barras',
                                  CANTIDAD_DIS = $cantidad,
                                  LABORATORIOS_idLABORATORIOS = $laboratorio_id 
                                  WHERE idINSUMOS = $id_insumo";
                    
                    if ($conexion->query($sql_insumo) === TRUE) {
                        $mensaje = "Insumo actualizado exitosamente.";
                        $modo_edicion_insumo = false;
                    } else {
                        $error = "Error al actualizar insumo: " . $conexion->error;
                        // MANTENER MODO EDICIÓN SI HAY ERROR EN LA CONSULTA
                        $modo_edicion_insumo = true;
                        $pagina_actual = 'insumos';
                    }
                } else {
                    // MODO REGISTRO: Insertar nuevo insumo
                    $sql_insumo = "INSERT INTO insumos (NOMBRE, DESCRIPCION, CODIGO_BARRAS, CANTIDAD_DIS, LABORATORIOS_idLABORATORIOS, ESTATUS) 
                                  VALUES ('$nombre_insumo', '$descripcion', '$codigo_barras', $cantidad, $laboratorio_id, 1)";
                    
                    if ($conexion->query($sql_insumo) === TRUE) {
                        $mensaje = "Insumo registrado exitosamente.";
                    } else {
                        $error = "Error al registrar insumo: " . $conexion->error;
                    }
                }
            }
        }
    }
}

// Si hay error y estamos en modo edición, recargar los datos del insumo
if (!empty($error) && $id_insumo > 0 && $modo_edicion_insumo) {
    $sql = "SELECT i.*, l.NOM_LAB 
            FROM insumos i 
            INNER JOIN laboratorios l ON i.LABORATORIOS_idLABORATORIOS = l.idLABORATORIOS 
            WHERE i.idINSUMOS = $id_insumo";
    $resultado = $conexion->query($sql);
    
    if ($resultado->num_rows > 0) {
        $datos_insumo = $resultado->fetch_assoc();
    }
}

// Procesar formulario de registro de reportes
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] == 'reporte') {
    // Limpiar y validar datos
    $tipo_incidencia = limpiarDatos($_POST['tipo_incidencia'], $conexion);
    $descripcion = limpiarDatos($_POST['descripcion'], $conexion);
    $elabora = limpiarDatos($_POST['elabora'], $conexion);
    $reporta = limpiarDatos($_POST['reporta'], $conexion);
    $responsable = limpiarDatos($_POST['responsable'], $conexion);
    $estatus = limpiarDatos($_POST['estatus'], $conexion);
    $accion = limpiarDatos($_POST['accion'], $conexion);
    $hora_incidente = limpiarDatos($_POST['hora_incidente'], $conexion);
    
    // Si se seleccionó "Otra" acción, usar el campo de texto
    if ($accion === 'Otra' && isset($_POST['otra_accion'])) {
        $accion = limpiarDatos($_POST['otra_accion'], $conexion);
    }
    
    // NUEVAS VALIDACIONES
    if (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $reporta)) {
        $error = "El campo 'Reportó' solo puede contener letras y espacios.";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $responsable)) {
        $error = "El campo 'Responsable' solo puede contener letras y espacios.";
    } elseif (empty($hora_incidente)) {
        $error = "Por favor, ingrese la hora del incidente.";
    } else {
        // Insertar nuevo reporte
        $sql_reporte = "INSERT INTO reportes (tipo_incidencia, descripcion, elabora, reporta, responsable, estatus, accion, fecha, hora_incidente) 
                       VALUES ('$tipo_incidencia', '$descripcion', '$elabora', '$reporta', '$responsable', '$estatus', '$accion', NOW(), '$hora_incidente')";
        
        if ($conexion->query($sql_reporte) === TRUE) {
            $mensaje = "Reporte registrado exitosamente.";
        } else {
            $error = "Error al registrar reporte: " . $conexion->error;
        }
    }
}

// Procesar eliminación de trabajador (dar de baja)
if (isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    
    if ($id_eliminar > 0) {
        // Solo marcar como inactivo en la tabla personas (la tabla usuarios no tiene ESTATUS)
        $sql_persona = "UPDATE personas SET ESTATUS = 0 WHERE idPERSONAS = $id_eliminar";
        
        if ($conexion->query($sql_persona) === TRUE) {
            $mensaje = "Trabajador desactivado exitosamente.";
        } else {
            $error = "Error al desactivar: " . $conexion->error;
        }
    }
}

// Procesar eliminación de laboratorio
if (isset($_GET['eliminar_lab'])) {
    $id_eliminar_lab = (int)$_GET['eliminar_lab'];
    
    if ($id_eliminar_lab > 0) {
        // Primero desactivar todos los insumos del laboratorio
        $sql_insumos = "UPDATE insumos SET ESTATUS = 0 WHERE LABORATORIOS_idLABORATORIOS = $id_eliminar_lab";
        $conexion->query($sql_insumos);
        
        // Luego eliminar el laboratorio
        $sql_lab = "DELETE FROM laboratorios WHERE idLABORATORIOS = $id_eliminar_lab";
        
        if ($conexion->query($sql_lab) === TRUE) {
            $mensaje = "Laboratorio eliminado exitosamente.";
        } else {
            $error = "Error al eliminar laboratorio: " . $conexion->error;
        }
    }
}

// Procesar eliminación de insumo (dar de baja)
if (isset($_GET['eliminar_insumo'])) {
    $id_eliminar_insumo = (int)$_GET['eliminar_insumo'];
    
    if ($id_eliminar_insumo > 0) {
        $sql_insumo = "UPDATE insumos SET ESTATUS = 0 WHERE idINSUMOS = $id_eliminar_insumo";
        
        if ($conexion->query($sql_insumo) === TRUE) {
            $mensaje = "Insumo desactivado exitosamente.";
        } else {
            $error = "Error al desactivar insumo: " . $conexion->error;
        }
    }
}

// Procesar eliminación de reporte
if (isset($_GET['eliminar_reporte'])) {
    $id_eliminar_reporte = (int)$_GET['eliminar_reporte'];
    
    if ($id_eliminar_reporte > 0) {
        $sql_reporte = "DELETE FROM reportes WHERE idReporte = $id_eliminar_reporte";
        
        if ($conexion->query($sql_reporte) === TRUE) {
            $mensaje = "Reporte eliminado exitosamente.";
        } else {
            $error = "Error al eliminar reporte: " . $conexion->error;
        }
    }
}

// Consultar trabajadores registrados (solo activos)
$sql_trabajadores = "SELECT p.*, u.NUM_USUARIO, u.FECHA_CREACION
                     FROM personas p 
                     INNER JOIN usuarios u ON p.idPERSONAS = u.PERSONAS_idPERSONAS 
                     WHERE p.ESTATUS = 1
                     ORDER BY p.idPERSONAS DESC";
$resultado_trabajadores = $conexion->query($sql_trabajadores);

// Consultar laboratorios
$sql_laboratorios = "SELECT l.*, p.NOMBRE, p.PATERNO, p.MATERNO 
                     FROM laboratorios l 
                     INNER JOIN personas p ON l.PERSONAS_idPERSONAS = p.idPERSONAS 
                     ORDER BY l.idLABORATORIOS DESC";
$resultado_laboratorios = $conexion->query($sql_laboratorios);

// Consultar insumos activos 
$sql_insumos = "SELECT i.*, l.NOM_LAB 
                FROM insumos i 
                INNER JOIN laboratorios l ON i.LABORATORIOS_idLABORATORIOS = l.idLABORATORIOS 
                WHERE i.ESTATUS = 1
                ORDER BY i.idINSUMOS DESC";
$resultado_insumos = $conexion->query($sql_insumos);

// Consultar reportes
$sql_reportes = "SELECT * FROM reportes ORDER BY fecha DESC";
$resultado_reportes = $conexion->query($sql_reportes);

// Consultar personas para el select de laboratorios
$sql_personas = "SELECT p.idPERSONAS, p.NOMBRE, p.PATERNO, p.MATERNO 
                 FROM personas p 
                 WHERE p.ESTATUS = 1 and p.ROL = 'trabajador'
                 ORDER BY p.NOMBRE, p.PATERNO";
$resultado_personas = $conexion->query($sql_personas);

// Consultar estadísticas para el dashboard
$sql_estadisticas = "SELECT 
                     COUNT(*) as total,
                     SUM(CASE WHEN p.ROL = 'trabajador' THEN 1 ELSE 0 END) as trabajadores,
                     SUM(CASE WHEN p.ROL = 'maestro' THEN 1 ELSE 0 END) as maestros,
                     SUM(CASE WHEN p.ROL = 'alumno' THEN 1 ELSE 0 END) as alumnos,
                     SUM(CASE WHEN p.ESTATUS = 1 THEN 1 ELSE 0 END) as activos,
                     SUM(CASE WHEN p.ESTATUS = 0 THEN 1 ELSE 0 END) as inactivos
                     FROM personas p 
                     INNER JOIN usuarios u ON p.idPERSONAS = u.PERSONAS_idPERSONAS";
$resultado_estadisticas = $conexion->query($sql_estadisticas);
$estadisticas = $resultado_estadisticas->fetch_assoc();

// Estadísticas adicionales para laboratorios e insumos
$sql_estadisticas_extra = "SELECT 
                          (SELECT COUNT(*) FROM laboratorios) as total_labs,
                          (SELECT COUNT(*) FROM insumos WHERE ESTATUS = 1) as total_insumos,
                          (SELECT SUM(CANTIDAD_DIS) FROM insumos WHERE ESTATUS = 1) as total_stock";
$resultado_estadisticas_extra = $conexion->query($sql_estadisticas_extra);
$estadisticas_extra = $resultado_estadisticas_extra->fetch_assoc();

// Estadísticas adicionales para reportes
$sql_estadisticas_reportes = "SELECT 
                             (SELECT COUNT(*) FROM reportes) as total_reportes,
                             (SELECT COUNT(*) FROM reportes WHERE estatus = 'Pendiente') as reportes_pendientes,
                             (SELECT COUNT(*) FROM reportes WHERE estatus = 'Resuelto') as reportes_resueltos";
$resultado_estadisticas_reportes = $conexion->query($sql_estadisticas_reportes);
$estadisticas_reportes = $resultado_estadisticas_reportes->fetch_assoc();

// Consultar trabajadores para el select de reportes
$sql_trabajadores_reportes = "SELECT p.NOMBRE, p.PATERNO, p.MATERNO 
                             FROM personas p 
                             WHERE p.ESTATUS = 1 
                             ORDER BY p.NOMBRE, p.PATERNO";
$resultado_trabajadores_reportes = $conexion->query($sql_trabajadores_reportes);

// Cerrar conexión
//$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión - <?php echo ucfirst($pagina_actual); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styleRegistroTrabajador.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar de navegación -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-cogs"></i> Panel de Control</h2>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($_SESSION['nombre']); ?></h3>
                    <p>Trabajador</p>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item <?php echo $pagina_actual == 'dashboard' ? 'active' : ''; ?>">
                        <a href="GestionTrabajador.php?pagina=dashboard">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $pagina_actual == 'gestion' ? 'active' : ''; ?>">
                        <a href="GestionTrabajador.php?pagina=gestion">
                            <i class="fas fa-users-cog"></i>
                            <span>Gestión Trabajadores</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $pagina_actual == 'laboratorios' ? 'active' : ''; ?>">
                        <a href="GestionTrabajador.php?pagina=laboratorios">
                            <i class="fas fa-flask"></i>
                            <span>Laboratorios</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $pagina_actual == 'insumos' ? 'active' : ''; ?>">
                        <a href="GestionTrabajador.php?pagina=insumos">
                            <i class="fas fa-boxes"></i>
                            <span>Insumos</span>
                        </a>
                    </li>
                    <!-- NUEVO: Menú de Reportes -->
                    <li class="nav-item <?php echo $pagina_actual == 'reportes' ? 'active' : ''; ?>">
                        <a href="GestionTrabajador.php?pagina=reportes">
                            <i class="fas fa-file-alt"></i>
                            <span>Reportes</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Cerrar Sesión</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        
        <!-- Contenido principal -->
        <div class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <button id="sidebarToggle" class="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>
                        <?php 
                        if ($pagina_actual == 'dashboard') echo 'Dashboard Principal';
                        elseif ($pagina_actual == 'gestion') echo 'Gestión de Trabajadores';
                        elseif ($pagina_actual == 'laboratorios') echo 'Gestión de Laboratorios';
                        elseif ($pagina_actual == 'insumos') echo 'Gestión de Insumos';
                        elseif ($pagina_actual == 'reportes') echo 'Gestión de Reportes';
                        else echo 'Sistema de Gestión';
                        ?>
                    </h1>
                </div>
                <div class="header-right">
                    <div class="user-menu">
                        <span><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                    </div>
                </div>
            </header>
            
            <div class="content">
                <!-- Mostrar mensajes de éxito o error -->
                <?php if (!empty($mensaje)): ?>
                    <div class="alert success">
                        <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Contenido del Dashboard -->
                <?php if ($pagina_actual == 'dashboard'): ?>
                    <!-- Tarjetas de resumen -->
                    <div class="summary-cards">
                        <div class="summary-card">
                            <div class="card-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="card-info">
                                <h3>Total Registros</h3>
                                <p><?php echo $estadisticas['total']; ?></p>
                            </div>
                        </div>
                        
                        <div class="summary-card">
                            <div class="card-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="card-info">
                                <h3>Trabajadores</h3>
                                <p><?php echo $estadisticas['trabajadores']; ?></p>
                            </div>
                        </div>
                        
                        <div class="summary-card">
                            <div class="card-icon">
                                <i class="fas fa-flask"></i>
                            </div>
                            <div class="card-info">
                                <h3>Laboratorios</h3>
                                <p><?php echo $estadisticas_extra['total_labs']; ?></p>
                            </div>
                        </div>
                        
                        <div class="summary-card">
                            <div class="card-icon">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <div class="card-info">
                                <h3>Insumos Activos</h3>
                                <p><?php echo $estadisticas_extra['total_insumos']; ?></p>
                            </div>
                        </div>

                        <!-- NUEVA: Tarjeta de Reportes -->
                        <div class="summary-card">
                            <div class="card-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="card-info">
                                <h3>Reportes</h3>
                                <p><?php echo $estadisticas_reportes['total_reportes']; ?></p>
                            </div>
                        </div>

                        <div class="summary-card">
                            <div class="card-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="card-info">
                                <h3>Pendientes</h3>
                                <p><?php echo $estadisticas_reportes['reportes_pendientes']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contenido adicional del dashboard -->
                    <div class="dashboard-sections">
                        <div class="section recent-activities">
                            <h2>Actividad Reciente</h2>
                            <ul class="activity-list">
                                <li>
                                    <i class="fas fa-user-plus activity-icon"></i>
                                    <div class="activity-details">
                                        <p>Nuevo trabajador registrado</p>
                                        <span>Hace 2 horas</span>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-flask activity-icon"></i>
                                    <div class="activity-details">
                                        <p>Laboratorio de Química agregado</p>
                                        <span>Hace 5 horas</span>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-file-alt activity-icon"></i>
                                    <div class="activity-details">
                                        <p>Nuevo reporte de incidencia</p>
                                        <span>Hace 1 hora</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="section quick-actions">
                            <h2>Acciones Rápidas</h2>
                            <div class="action-buttons">
                                <a href="GestionTrabajador.php?pagina=gestion" class="action-btn">
                                    <i class="fas fa-users-cog"></i>
                                    <span>Gestionar Trabajadores</span>
                                </a>
                                <a href="GestionTrabajador.php?pagina=laboratorios" class="action-btn">
                                    <i class="fas fa-flask"></i>
                                    <span>Gestionar Laboratorios</span>
                                </a>
                                <a href="GestionTrabajador.php?pagina=insumos" class="action-btn">
                                    <i class="fas fa-boxes"></i>
                                    <span>Gestionar Insumos</span>
                                </a>
                                <a href="GestionTrabajador.php?pagina=reportes" class="action-btn">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Gestionar Reportes</span>
                                </a>
                            </div>
                        </div>
                    </div>
                
                <!-- Contenido de Gestión de Trabajadores -->
                <?php elseif ($pagina_actual == 'gestion'): ?>
                    <!-- Formulario de registro/edición -->
                    <div class="form-section">
                        <h2><?php echo $modo_edicion ? 'Editar Trabajador' : 'Registrar Nuevo Trabajador'; ?></h2>
                        
                        <form method="POST" action="GestionTrabajador.php?pagina=gestion" class="registro-form">
                            <input type="hidden" name="form_type" value="trabajador">
                            <?php if ($modo_edicion): ?>
                                <input type="hidden" name="id" value="<?php echo $id_edicion; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nombre">Nombre *</label>
                                    <input type="text" id="nombre" name="nombre" 
                                           value="<?php echo ($modo_edicion && isset($datos_trabajador['NOMBRE'])) ? htmlspecialchars($datos_trabajador['NOMBRE']) : ''; ?>"
                                           pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+" required
                                           title="Solo se permiten letras y espacios">
                                </div>
                                
                                <div class="form-group">
                                    <label for="paterno">Apellido Paterno *</label>
                                    <input type="text" id="paterno" name="paterno" 
                                           value="<?php echo ($modo_edicion && isset($datos_trabajador['PATERNO'])) ? htmlspecialchars($datos_trabajador['PATERNO']) : ''; ?>" 
                                           pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+" required 
                                           title="Solo se permiten letras y espacios">
                                </div>
                                
                                <div class="form-group">
                                    <label for="materno">Apellido Materno</label>
                                    <input type="text" id="materno" name="materno" 
                                           value="<?php echo ($modo_edicion && isset($datos_trabajador['MATERNO'])) ? htmlspecialchars($datos_trabajador['MATERNO']) : ''; ?>" 
                                           pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+" 
                                           title="Solo se permiten letras y espacios">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="correo">Correo Electrónico *</label>
                                    <input type="email" id="correo" name="correo" 
                                            value="<?php echo ($modo_edicion && isset($datos_trabajador['CORREO'])) ? htmlspecialchars($datos_trabajador['CORREO']) : ''; ?>" 
                                            pattern="^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.com$" required
                                            title="El correo debe contener '@' y terminar en .com">
                                </div>
                                
                                <div class="form-group">
                                    <label for="telefono">Teléfono *</label>
                                    <input type="tel" id="telefono" name="telefono" 
                                           value="<?php echo $modo_edicion ? htmlspecialchars($datos_trabajador['TELEFONO']) : ''; ?>" 
                                           pattern="[0-9]{10}" maxlength="10" required
                                           title="El número de teléfono debe contener exactamente 10 dígitos">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="numero_control">Número de Control *</label>
                                    <input type="text" id="numero_control" name="numero_control" 
                                           value="<?php echo $modo_edicion ? htmlspecialchars($datos_trabajador['NUM_USUARIO']) : ''; ?>" 
                                           maxlength="8" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="pin">PIN * (4 dígitos)</label>
                                    <input type="text" id="pin" name="pin" 
                                           value="<?php echo $modo_edicion ? htmlspecialchars($datos_trabajador['PIN']) : ''; ?>" 
                                           maxlength="4" pattern="\d{4}" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="rol">Rol *</label>
                                    <select id="rol" name="rol" required>
                                        <option value="trabajador" <?php echo ($modo_edicion && $datos_trabajador['ROL'] == 'trabajador') ? 'selected' : ''; ?>>Trabajador</option>
                                        <option value="maestro" <?php echo ($modo_edicion && $datos_trabajador['ROL'] == 'maestro') ? 'selected' : ''; ?>>Maestro</option>
                                        <option value="alumno" <?php echo ($modo_edicion && $datos_trabajador['ROL'] == 'alumno') ? 'selected' : ''; ?>>Alumno</option>
                                    </select>
                                </div>
                                
                                <?php if ($modo_edicion): ?>
                                    <div class="form-group">
                                        <label for="estatus">Estatus *</label>
                                        <select id="estatus" name="estatus" required>
                                            <option value="1" <?php echo $datos_trabajador['ESTATUS'] == 1 ? 'selected' : ''; ?>>Activo</option>
                                            <option value="0" <?php echo $datos_trabajador['ESTATUS'] == 0 ? 'selected' : ''; ?>>Inactivo</option>
                                        </select>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="estatus" value="1">
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-registrar">
                                    <?php echo $modo_edicion ? 'Actualizar Trabajador' : 'Registrar Trabajador'; ?>
                                </button>
                                
                                <?php if ($modo_edicion): ?>
                                    <a href="GestionTrabajador.php?pagina=gestion" class="btn-cancelar">Cancelar</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Lista de trabajadores registrados -->
                    <div class="table-section">
                        <h2>Trabajadores Registrados</h2>
                        
                        <!-- Barra de búsqueda y filtros -->
                        <div class="content-toolbar">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Buscar trabajadores..." id="buscarTrabajadores">
                            </div>
                            <div class="filters">
                                <select id="filtroRol">
                                    <option value="">Todos los roles</option>
                                    <option value="trabajador">Trabajadores</option>
                                    <option value="maestro">Maestros</option>
                                    <option value="alumno">Alumnos</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Tabla de trabajadores -->
                        <div class="table-container">
                            <?php if ($resultado_trabajadores->num_rows > 0): ?>
                                <table class="data-table" id="tablaTrabajadores">
                                    <thead>
                                        <tr>
                                            <th>Nombre Completo</th>
                                            <th>Correo</th>
                                            <th>Teléfono</th>
                                            <th>Número de Control</th>
                                            <th>Rol</th>
                                            <th>Fecha Registro</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($fila = $resultado_trabajadores->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($fila['NOMBRE'] . ' ' . $fila['PATERNO'] . ' ' . $fila['MATERNO']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['CORREO']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['TELEFONO']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['NUM_USUARIO']); ?></td>
                                                <td><?php echo ucfirst($fila['ROL']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($fila['FECHA_CREACION'])); ?></td>
                                                <td class="actions">
                                                    <a href="GestionTrabajador.php?pagina=gestion&editar=<?php echo $fila['idPERSONAS']; ?>" class="btn-action edit" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="GestionTrabajador.php?pagina=gestion&eliminar=<?php echo $fila['idPERSONAS']; ?>" class="btn-action delete" title="Dar de baja" onclick="return confirm('¿Estás seguro de dar de baja a este trabajador/maestro?')">
                                                        <i class="fas fa-user-times"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-users-slash"></i>
                                    <h3>No hay trabajadores registrados</h3>
                                    <p>Comienza registrando el primer trabajador en el sistema</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <!-- Contenido de Gestión de Laboratorios -->
                <?php elseif ($pagina_actual == 'laboratorios'): ?>
                    <!-- Formulario de registro/edición de laboratorios -->
                    <div class="form-section">
                        <h2><?php echo $modo_edicion_lab ? 'Editar Laboratorio' : 'Registrar Nuevo Laboratorio'; ?></h2>
                        
                       <form method="POST" action="GestionTrabajador.php?pagina=laboratorios<?php echo $modo_edicion_lab ? '&editar_lab=' . $id_edicion_lab : ''; ?>" class="registro-form">
                            <input type="hidden" name="form_type" value="laboratorio">
                            <?php if ($modo_edicion_lab): ?>
                                <input type="hidden" name="id_lab" value="<?php echo $id_edicion_lab; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nombre_lab">Nombre del Laboratorio *</label>
                                    <input type="text" id="nombre_lab" name="nombre_lab" 
                                        value="<?php echo $modo_edicion_lab ? htmlspecialchars($datos_laboratorio['NOM_LAB']) : ''; ?>" 
                                        pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]{1,50}" 
                                        title="Solo letras y espacios, máximo 50 caracteres" 
                                        required maxlength="50">
                                    <small class="form-text">Solo letras y espacios, máximo 50 caracteres</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="encargado_lab">Encargado *</label>
                                    <input type="text" id="encargado_lab" name="encargado_lab" 
                                        value="<?php echo htmlspecialchars($_SESSION['nombre']); ?>" 
                                        readonly class="disabled">
                                    <small class="form-text">Se asigna automáticamente con su usuario</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="horario">Horario De Disponibilidad</label>
                                    <input type="time" id="horario" name="horario" 
                                        value="<?php echo $modo_edicion_lab ? htmlspecialchars($datos_laboratorio['HORARIO']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="ubicacion">Ubicación *</label>
                                    <input type="text" id="ubicacion" name="ubicacion" 
                                        value="<?php echo $modo_edicion_lab ? htmlspecialchars($datos_laboratorio['UBICACION']) : ''; ?>" 
                                        pattern="[A-Za-z0-9áéíóúÁÉÍÓÚñÑ\s]{1,30}" 
                                        title="Solo letras y números, máximo 30 caracteres" 
                                        required maxlength="30">
                                    <small class="form-text">Solo letras y números, máximo 30 caracteres</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="persona_id">Persona Asociada *</label>
                                    <select id="persona_id" name="persona_id" required>
                                        <option value="">Seleccione una persona</option>
                                        <?php 
                                        if ($resultado_personas && $resultado_personas->num_rows > 0):
                                            $resultado_personas->data_seek(0);
                                            while($persona = $resultado_personas->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $persona['idPERSONAS']; ?>" 
                                                <?php echo ($modo_edicion_lab && isset($datos_laboratorio['PERSONAS_idPERSONAS']) && $datos_laboratorio['PERSONAS_idPERSONAS'] == $persona['idPERSONAS']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($persona['NOMBRE'] . ' ' . $persona['PATERNO'] . ' ' . $persona['MATERNO']); ?>
                                            </option>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <option value="">No hay personas disponibles</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-registrar">
                                    <?php echo $modo_edicion_lab ? 'Actualizar Laboratorio' : 'Registrar Laboratorio'; ?>
                                </button>
                                
                                <?php if ($modo_edicion_lab): ?>
                                    <a href="GestionTrabajador.php?pagina=laboratorios" class="btn-cancelar">Cancelar</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Lista de laboratorios registrados -->
                    <div class="table-section">
                        <h2>Laboratorios Registrados</h2>
                        
                        <!-- Barra de búsqueda y filtros -->
                        <div class="content-toolbar">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Buscar laboratorios..." id="buscarLaboratorios">
                            </div>
                        </div>
                        
                        <!-- Tabla de laboratorios -->
                        <div class="table-container">
                            <?php if ($resultado_laboratorios && $resultado_laboratorios->num_rows > 0): ?>
                                <table class="data-table" id="tablaLaboratorios">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Encargado</th>
                                            <th>Horario</th>
                                            <th>Ubicación</th>
                                            <th>Persona Asociada</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($fila = $resultado_laboratorios->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($fila['NOM_LAB']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['ENCARGADO_LAB']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['HORARIO']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['UBICACION']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['NOMBRE'] . ' ' . $fila['PATERNO'] . ' ' . $fila['MATERNO']); ?></td>
                                                <td class="actions">
                                                    <a href="GestionTrabajador.php?pagina=laboratorios&editar_lab=<?php echo $fila['idLABORATORIOS']; ?>" class="btn-action edit" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="GestionTrabajador.php?pagina=laboratorios&eliminar_lab=<?php echo $fila['idLABORATORIOS']; ?>" class="btn-action delete" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este laboratorio? Se desactivarán todos sus insumos.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-flask"></i>
                                    <h3>No hay laboratorios registrados</h3>
                                    <p>Comienza registrando el primer laboratorio en el sistema usando el formulario superior</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <!-- Contenido de Gestión de Insumos -->
                <?php elseif ($pagina_actual == 'insumos'): ?>
                    <!-- Formulario de registro/edición de insumos -->
                    <div class="form-section">
                        <h2><?php echo $modo_edicion_insumo ? 'Editar Insumo' : 'Registrar Nuevo Insumo'; ?></h2>
                        
                        <form method="POST" action="GestionTrabajador.php?pagina=insumos<?php echo $modo_edicion_insumo ? '&editar_insumo=' . $id_edicion_insumo : ''; ?>" class="registro-form">
                            <input type="hidden" name="form_type" value="insumo">
                            <?php if ($modo_edicion_insumo): ?>
                                <input type="hidden" name="id_insumo" value="<?php echo $id_edicion_insumo; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nombre_insumo">Nombre del Insumo *</label>
                                    <input type="text" id="nombre_insumo" name="nombre_insumo" 
                                        value="<?php echo $modo_edicion_insumo ? htmlspecialchars($datos_insumo['NOMBRE']) : ''; ?>" 
                                        pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]{1,30}" 
                                        title="Solo letras y espacios, máximo 30 caracteres" 
                                        required maxlength="30">
                                    <small class="form-text">Solo letras y espacios, máximo 30 caracteres</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="codigo_barras">Código de Barras *</label>
                                    <input type="text" id="codigo_barras" name="codigo_barras" 
                                        value="<?php echo $modo_edicion_insumo ? htmlspecialchars($datos_insumo['CODIGO_BARRAS']) : ''; ?>" 
                                        pattern="[0-9]{1,50}" 
                                        title="Solo números, máximo 50 caracteres" 
                                        required maxlength="50" placeholder="Ingrese el código de barras">
                                    <small class="form-text">Solo números, máximo 50 caracteres. Debe ser único.</small>
                                </div>

                                <button type="button" id="generarCodigo" class="btn-generar-codigo">
                                    <i class="fas fa-barcode"></i> Generar Código
                                </button>

                                <div class="form-group">
                                    <label for="cantidad">Cantidad Disponible *</label>
                                    <input type="number" id="cantidad" name="cantidad" 
                                        value="<?php echo $modo_edicion_insumo ? htmlspecialchars($datos_insumo['CANTIDAD_DIS']) : '0'; ?>" 
                                        min="0" max="999" required>
                                    <small class="form-text">Máximo 3 dígitos (0-999)</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="laboratorio_id">Laboratorio *</label>
                                    <select id="laboratorio_id" name="laboratorio_id" required>
                                        <option value="">Seleccione un laboratorio</option>
                                        <?php 
                                        $resultado_laboratorios_select = $conexion->query("SELECT * FROM laboratorios ORDER BY NOM_LAB");
                                        if ($resultado_laboratorios_select && $resultado_laboratorios_select->num_rows > 0):
                                            while($lab = $resultado_laboratorios_select->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $lab['idLABORATORIOS']; ?>" 
                                                <?php echo ($modo_edicion_insumo && $datos_insumo['LABORATORIOS_idLABORATORIOS'] == $lab['idLABORATORIOS']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($lab['NOM_LAB']); ?>
                                            </option>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <option value="">No hay laboratorios disponibles</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="descripcion">Descripción *</label>
                                    <textarea id="descripcion" name="descripcion" rows="5" required maxlength="500" placeholder="Describa detalladamente el insumo..."><?php echo $modo_edicion_insumo ? htmlspecialchars($datos_insumo['DESCRIPCION']) : ''; ?></textarea>
                                    <small class="form-text">Máximo 500 caracteres</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="encargado_lab">Encargado *</label>
                                <input type="text" id="encargado_lab" name="encargado_lab" 
                                    value="<?php echo htmlspecialchars($_SESSION['nombre']); ?>" 
                                    readonly class="disabled">
                                <small class="form-text">Se asigna automáticamente con su usuario</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-registrar">
                                    <?php echo $modo_edicion_insumo ? 'Actualizar Insumo' : 'Registrar Insumo'; ?>
                                </button>
                                
                                <?php if ($modo_edicion_insumo): ?>
                                    <a href="GestionTrabajador.php?pagina=insumos" class="btn-cancelar">Cancelar</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Lista de insumos registrados -->
                    <div class="table-section">
                        <h2>Insumos Registrados</h2>
                        
                        <!-- Barra de búsqueda y filtros -->
                        <div class="content-toolbar">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Buscar insumos..." id="buscarInsumos">
                            </div>
                            <div class="filters">
                                <select id="filtroLaboratorio">
                                    <option value="">Todos los laboratorios</option>
                                    <?php 
                                    $resultado_labs_filter = $conexion->query("SELECT * FROM laboratorios ORDER BY NOM_LAB");
                                    if ($resultado_labs_filter && $resultado_labs_filter->num_rows > 0):
                                        while($lab = $resultado_labs_filter->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($lab['NOM_LAB']); ?>">
                                            <?php echo htmlspecialchars($lab['NOM_LAB']); ?>
                                        </option>
                                    <?php 
                                        endwhile;
                                    endif;
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Tabla de insumos -->
                        <div class="table-container">
                            <?php if ($resultado_insumos && $resultado_insumos->num_rows > 0): ?>
                                <table class="data-table" id="tablaInsumos">
                                    <thead>
                                        <tr>
                                            <th>Código Barras</th>
                                            <th>Nombre</th>
                                            <th>Descripción</th>
                                            <th>Cantidad</th>
                                            <th>Laboratorio</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($fila = $resultado_insumos->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($fila['CODIGO_BARRAS'])): ?>
                                                        <span class="codigo-barras"><?php echo htmlspecialchars($fila['CODIGO_BARRAS']); ?></span>
                                                        <button class="btn-copiar" onclick="copiarCodigo('<?php echo $fila['CODIGO_BARRAS']; ?>')" title="Copiar código">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="sin-codigo">Sin código</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($fila['NOMBRE']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['DESCRIPCION']); ?></td>
                                                <td>
                                                    <span class="cantidad-badge <?php echo $fila['CANTIDAD_DIS'] == 0 ? 'agotado' : ($fila['CANTIDAD_DIS'] < 10 ? 'bajo' : 'normal'); ?>">
                                                        <?php echo $fila['CANTIDAD_DIS']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($fila['NOM_LAB']); ?></td>
                                                <td class="actions">
                                                    <a href="GestionTrabajador.php?pagina=insumos&editar_insumo=<?php echo $fila['idINSUMOS']; ?>" class="btn-action edit" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="GestionTrabajador.php?pagina=insumos&eliminar_insumo=<?php echo $fila['idINSUMOS']; ?>" class="btn-action delete" title="Dar de Baja" onclick="return confirm('¿Estás seguro de dar de baja este insumo?')">
                                                        <i class="fas fa-ban"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-boxes"></i>
                                    <h3>No hay insumos registrados</h3>
                                    <p>Comienza registrando el primer insumo en el sistema usando el formulario superior</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <!-- NUEVO: Contenido de Gestión de Reportes -->
                    <?php elseif ($pagina_actual == 'reportes'): ?>
                        <!-- Formulario de registro de reportes -->
                        <div class="form-section">
                            <h2><i class="fas fa-clipboard-list"></i> Registrar Nuevo Reporte</h2>
                            
                            <form method="POST" action="GestionTrabajador.php?pagina=reportes" class="registro-form">
                                <input type="hidden" name="form_type" value="reporte">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="tipo_incidencia">Tipo de Incidencia *</label>
                                        <select id="tipo_incidencia" name="tipo_incidencia" required>
                                            <option value="">Selecciona una opción</option>
                                            <option value="Pérdida de material">Pérdida de material</option>
                                            <option value="Daño de equipo">Daño de equipo</option>
                                            <option value="Falta de insumos">Falta de insumos</option>
                                            <option value="Problema de infraestructura">Problema de infraestructura</option>
                                            <option value="Otro">Otro</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="estatus">Estatus *</label>
                                        <select id="estatus" name="estatus" required>
                                            <option value="Pendiente">Pendiente</option>
                                            <option value="En revisión">En revisión</option>
                                            <option value="Resuelto">Resuelto</option>
                                        </select>
                                    </div>

                                    <!-- NUEVO: Campo para hora del incidente -->
                                    <div class="form-group">
                                        <label for="hora_incidente">Hora del Incidente *</label>
                                        <input type="time" id="hora_incidente" name="hora_incidente" required>
                                        <small class="form-text">Hora aproximada en que ocurrió el incidente</small>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="elabora">Elaboró *</label>
                                        <select id="elabora" name="elabora" required>
                                            <option value="">Seleccione un trabajador</option>
                                            <?php 
                                            $usuarioActual = htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['paterno'] . ' ' . $_SESSION['materno']);
                                            
                                            if ($resultado_trabajadores_reportes && $resultado_trabajadores_reportes->num_rows > 0):
                                                $resultado_trabajadores_reportes->data_seek(0);
                                                while($trabajador = $resultado_trabajadores_reportes->fetch_assoc()): 
                                                    $nombreCompleto = htmlspecialchars($trabajador['NOMBRE'] . ' ' . $trabajador['PATERNO'] . ' ' . $trabajador['MATERNO']);
                                                    $selected = ($nombreCompleto === $usuarioActual) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $nombreCompleto; ?>" <?php echo $selected; ?>>
                                                    <?php echo $nombreCompleto; ?>
                                                </option>
                                            <?php 
                                                endwhile;
                                            else:
                                            ?>
                                                <option value="">No hay trabajadores disponibles</option>
                                            <?php endif; ?>
                                        </select>
                                        <small class="form-text">Seleccione el trabajador que elabora el reporte</small>
                                    </div>
                                                                        
                                    <div class="form-group">
                                        <label for="reporta">Reportó *</label>
                                        <input type="text" id="reporta" name="reporta" required
                                            pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]+"
                                            title="Solo se permiten letras y espacios"
                                            placeholder="Persona que reportó la incidencia">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="responsable">Responsable *</label>
                                        <input type="text" id="responsable" name="responsable" required
                                            pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]+"
                                            title="Solo se permiten letras y espacios"
                                            placeholder="Persona responsable de la incidencia">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group full-width">
                                        <label for="descripcion">Descripción de la Incidencia *</label>
                                        <textarea id="descripcion" name="descripcion" rows="4" required
                                                placeholder="Describa detalladamente la incidencia..."></textarea>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group full-width">
                                        <label for="accion">Acción Tomada *</label>
                                        <select id="accion" name="accion" required>
                                            <option value="">Selecciona una acción</option>
                                            <option value="Se levantó carta de adeudo">Se levantó carta de adeudo</option>
                                            <option value="El responsable pagó el daño">El responsable pagó el daño</option>
                                            <option value="El responsable reparó el daño">El responsable reparó el daño</option>
                                            <option value="En proceso de resolución">En proceso de resolución</option>
                                            <option value="Derivado a administración">Derivado a administración</option>
                                            <option value="Otra">Otra</option>
                                        </select>
                                        <small class="form-text">Seleccione la acción tomada para resolver la incidencia</small>
                                    </div>
                                </div>

                                <div class="form-row" id="otra_accion_container" style="display: none;">
                                    <div class="form-group full-width">
                                        <label for="otra_accion">Especifique otra acción:</label>
                                        <input type="text" id="otra_accion" name="otra_accion" 
                                            placeholder="Describa la acción tomada...">
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn-registrar">
                                        <i class="fas fa-plus-circle"></i> Registrar Reporte
                                    </button>
                                </div>
                            </form>
                        </div>
                    
                    <!-- Lista de reportes registrados -->
                    <div class="table-section">
                        <h2><i class="fas fa-list"></i> Reportes Registrados</h2>
                        
                        <!-- Barra de búsqueda y filtros -->
                        <div class="content-toolbar">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Buscar reportes..." id="buscarReportes">
                            </div>
                            <div class="filters">
                                <select id="filtroEstatus">
                                    <option value="">Todos los estatus</option>
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="En revisión">En revisión</option>
                                    <option value="Resuelto">Resuelto</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Tabla de reportes -->
                            <div class="table-container">
                                <?php if ($resultado_reportes && $resultado_reportes->num_rows > 0): ?>
                                    <table class="data-table" id="tablaReportes">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Tipo Incidencia</th>
                                                <th>Descripción</th>
                                                <th>Elaboró</th>
                                                <th>Reportó</th>
                                                <th>Responsable</th>
                                                <th>Estatus</th>
                                                <th>Acción</th>
                                                <th>Hora Incidente</th>
                                                <th>Fecha Reporte</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($fila = $resultado_reportes->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $fila['idReporte']; ?></td>
                                                    <td><?php echo htmlspecialchars($fila['tipo_incidencia']); ?></td>
                                                    <td><?php echo htmlspecialchars($fila['descripcion']); ?></td>
                                                    <td><?php echo htmlspecialchars($fila['elabora']); ?></td>
                                                    <td><?php echo htmlspecialchars($fila['reporta']); ?></td>
                                                    <td><?php echo htmlspecialchars($fila['responsable']); ?></td>
                                                    <td>
                                                        <span class="estatus-badge <?php echo strtolower(str_replace(' ', '-', $fila['estatus'])); ?>">
                                                            <?php echo $fila['estatus']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($fila['accion']); ?></td>
                                                    <td>
                                                        <?php 
                                                        if (!empty($fila['hora_incidente'])) {
                                                            echo date('H:i', strtotime($fila['hora_incidente']));
                                                        } else {
                                                            echo '--:--';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($fila['fecha'])); ?></td>
                                                    <td class="actions">
                                                        <a href="GestionTrabajador.php?pagina=reportes&eliminar_reporte=<?php echo $fila['idReporte']; ?>" 
                                                        class="btn-action delete" 
                                                        title="Eliminar"
                                                        onclick="return confirm('¿Estás seguro de eliminar este reporte?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-file-alt"></i>
                                        <h3>No hay reportes registrados</h3>
                                        <p>Comienza registrando el primer reporte usando el formulario superior</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="js/scriptRegistroTrabajador.js"></script>
</body>
</html>
<?php
$conexion->close();
?>