<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

// Verificar si el usuario ha iniciado sesión y es un maestro
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'maestro') {
    header("Location: index.php");
    exit();
}

// DEPURACIÓN: Verificar contenido de la sesión
error_log("DEBUG SESSION: " . print_r($_SESSION, true));

// Incluir archivo de conexión
require_once 'Conexion/conexion.php';

// Asegurarse de que existe idPERSONAS en la sesión - CORREGIDO
if (!isset($_SESSION['idPERSONAS']) || $_SESSION['idPERSONAS'] <= 0) {
    // Si no tenemos idPERSONAS, intentar usar id_persona como respaldo
    if (isset($_SESSION['id_persona']) && $_SESSION['id_persona'] > 0) {
        $_SESSION['idPERSONAS'] = $_SESSION['id_persona'];
        $_SESSION['id_usuario'] = $_SESSION['id_persona'];
    } else {
        // Si tampoco tenemos id_persona, intentar recuperar desde la BD
        if (isset($_SESSION['numero_control'])) {
            $numero_control = $_SESSION['numero_control'];
            $sql_recuperar = "SELECT p.idPERSONAS FROM personas p 
                             INNER JOIN usuarios u ON p.idPERSONAS = u.PERSONAS_idPERSONAS 
                             WHERE u.NUM_USUARIO = ? AND p.ROL = 'maestro'";
            $stmt = $conexion->prepare($sql_recuperar);
            $stmt->bind_param("s", $numero_control);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                $datos = $resultado->fetch_assoc();
                $_SESSION['idPERSONAS'] = $datos['idPERSONAS'];
                $_SESSION['id_usuario'] = $datos['idPERSONAS'];
                $_SESSION['id_persona'] = $datos['idPERSONAS'];
            }
            $stmt->close();
        }
    }
}

// Si aún no tenemos el ID, mostrar error específico
if (!isset($_SESSION['idPERSONAS']) || $_SESSION['idPERSONAS'] <= 0) {
    die("Error: No se pudo identificar al usuario maestro. ID de persona no encontrado en sesión. 
         Por favor, cierre sesión y vuelva a ingresar. 
         Datos de sesión: " . print_r($_SESSION, true));
}

// =============================================
// ENDPOINT PARA OBTENER INSUMOS (AJAX)
// =============================================
if (isset($_GET['action']) && $_GET['action'] == 'obtener_insumos') {
    header('Content-Type: application/json');
    
    $id_laboratorio = isset($_GET['laboratorio']) ? (int)$_GET['laboratorio'] : 0;
    
    if ($id_laboratorio <= 0) {
        echo json_encode(['error' => 'ID de laboratorio inválido']);
        exit();
    }
    
    try {
        // Por esta (agregando la condición CANTIDAD_DIS > 0):
        $sql = "SELECT i.idINSUMOS, i.NOMBRE, i.DESCRIPCION, i.CODIGO_BARRAS, i.CANTIDAD_DIS 
                FROM insumos i 
                WHERE i.LABORATORIOS_idLABORATORIOS = ? 
                AND i.CANTIDAD_DIS > 0
                ORDER BY i.NOMBRE";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_laboratorio);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $insumos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $insumos[] = $fila;
        }
        
        $stmt->close();
        echo json_encode($insumos);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
    }
    
    exit();
}


// Inicializar variables
$mensaje = "";
$error = "";
$datos_alumno = null;
$modo_edicion = false;
$pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'dashboard';

// Procesar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Asegurarse de que existe id_usuario en la sesión
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['id_usuario'] = $_SESSION['idPERSONAS'] ?? 0;
}

// Determinar si estamos editando un alumno existente
$id_edicion = isset($_GET['editar']) ? (int)$_GET['editar'] : 0;

// Obtener datos del alumno para edición
if ($id_edicion > 0) {
    $sql = "SELECT p.*, u.NUM_USUARIO, u.PIN
            FROM personas p 
            INNER JOIN usuarios u ON p.idPERSONAS = u.PERSONAS_idPERSONAS 
            WHERE p.idPERSONAS = $id_edicion AND p.ROL = 'alumno'";
    $resultado = $conexion->query($sql);
    
    if ($resultado->num_rows > 0) {
        $datos_alumno = $resultado->fetch_assoc();
        $modo_edicion = true;
        $pagina_actual = 'gestion';
    } else {
        $error = "Alumno no encontrado.";
    }
}

// Procesar formulario de registro/edición
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['solicitar_insumos'])) {
    // Limpiar y validar datos CON VERIFICACIÓN DE isset
    $nombre = isset($_POST['nombre']) ? limpiarDatos($_POST['nombre'], $conexion) : '';
    $paterno = isset($_POST['paterno']) ? limpiarDatos($_POST['paterno'], $conexion) : '';
    $materno = isset($_POST['materno']) ? limpiarDatos($_POST['materno'], $conexion) : '';
    $correo = isset($_POST['correo']) ? limpiarDatos($_POST['correo'], $conexion) : '';
    $telefono = isset($_POST['telefono']) ? limpiarDatos($_POST['telefono'], $conexion) : '';
    $numero_control = isset($_POST['numero_control']) ? limpiarDatos($_POST['numero_control'], $conexion) : '';
    $pin = isset($_POST['pin']) ? limpiarDatos($_POST['pin'], $conexion) : '';
    $rol = 'alumno';
    $estatus = isset($_POST['estatus']) ? (int)$_POST['estatus'] : 1;
    
    // VALORES POR DEFECTO EN LA APLICACIÓN (no en la BD)
    $semestre = isset($_POST['semestre']) && !empty($_POST['semestre']) ? (int)$_POST['semestre'] : 1;
    $carrera = isset($_POST['carrera']) && !empty($_POST['carrera']) ? limpiarDatos($_POST['carrera'], $conexion) : 'Sistemas';
    
    // Obtener ID si estamos en modo edición
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    // Validaciones básicas
    if (empty($nombre) || empty($paterno) || empty($numero_control) || empty($pin)) {
        $error = "Por favor, complete todos los campos obligatorios.";
    } elseif (strlen($pin) != 4 || !is_numeric($pin)) {
        $error = "El PIN debe tener exactamente 4 dígitos numéricos.";
    } elseif (!is_numeric($numero_control)) {
        $error = "El número de control solo debe contener números.";
    } elseif ($semestre < 1 || $semestre > 12) {
        $error = "Por favor seleccione un semestre válido (1 a 12).";
    } elseif (empty($carrera)) {
        $error = "Por favor seleccione una carrera.";
    } elseif (!empty($telefono) && !preg_match('/^[0-9]{10}$/', $telefono)) {
        $error = "El número de teléfono debe contener exactamente 10 dígitos.";
    } elseif (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/', $nombre)) {
        $error = "El nombre solo puede contener letras y espacios.";
    } elseif (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/', $paterno)) {
        $error = "El apellido paterno solo puede contener letras y espacios.";
    } elseif (!empty($materno) && !preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/', $materno)) {
        $error = "El apellido materno solo puede contener letras y espacios.";
    } elseif (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "El correo electrónico no tiene un formato válido.";
    } 
    // NUEVAS VALIDACIONES DE LONGITUD
    elseif (strlen($nombre) > 30) {
        $error = "El nombre no puede tener más de 30 caracteres.";
    } elseif (strlen($paterno) > 30) {
        $error = "El apellido paterno no puede tener más de 30 caracteres.";
    } elseif (!empty($materno) && strlen($materno) > 30) {
        $error = "El apellido materno no puede tener más de 30 caracteres.";
    } elseif (!empty($correo) && strlen($correo) > 200) {
        $error = "El correo electrónico no puede tener más de 200 caracteres.";
    } else {
        // VALIDACIÓN: Número de control único
        $sql_check_numero = "SELECT COUNT(*) as total FROM usuarios WHERE NUM_USUARIO = '$numero_control'";
        if ($id > 0) {
            $sql_check_numero .= " AND PERSONAS_idPERSONAS != $id";
        }
        $result_numero = $conexion->query($sql_check_numero);
        $existe_numero = $result_numero->fetch_assoc()['total'] > 0;
        
        // VALIDACIÓN: Teléfono único (solo si se proporcionó teléfono)
        $existe_telefono = false;
        if (!empty($telefono)) {
            $sql_check_telefono = "SELECT COUNT(*) as total FROM personas WHERE TELEFONO = '$telefono' AND TELEFONO != ''";
            if ($id > 0) {
                $sql_check_telefono .= " AND idPERSONAS != $id";
            }
            $result_telefono = $conexion->query($sql_check_telefono);
            $existe_telefono = $result_telefono->fetch_assoc()['total'] > 0;
        }
        
        // VALIDACIÓN: Correo único (solo si se proporcionó correo)
        $existe_correo = false;
        if (!empty($correo)) {
            $sql_check_correo = "SELECT COUNT(*) as total FROM personas WHERE CORREO = '$correo' AND CORREO != ''";
            if ($id > 0) {
                $sql_check_correo .= " AND idPERSONAS != $id";
            }
            $result_correo = $conexion->query($sql_check_correo);
            $existe_correo = $result_correo->fetch_assoc()['total'] > 0;
        }
        
        if ($existe_numero) {
            $error = "El número de control ya está registrado en el sistema.";
        } elseif ($existe_telefono) {
            $error = "El número de teléfono ya está registrado en el sistema.";
        } elseif ($existe_correo) {
            $error = "El correo electrónico ya está registrado en el sistema.";
        } else {
            // Iniciar transacción
            $conexion->begin_transaction();
            
            try {
                if ($id > 0) {
                    // MODO EDICIÓN: Actualizar registro existente
                    $sql_persona = "UPDATE personas SET 
                                    NOMBRE = '$nombre', 
                                    PATERNO = '$paterno', 
                                    MATERNO = '$materno', 
                                    CORREO = '$correo', 
                                    TELEFONO = '$telefono', 
                                    SEMESTRE = $semestre, 
                                    CARRERA = '$carrera',
                                    ROL = '$rol',
                                    ESTATUS = $estatus 
                                    WHERE idPERSONAS = $id";
                    
                    if ($conexion->query($sql_persona) === TRUE) {
                        // Actualizar datos en usuarios
                        $sql_usuario = "UPDATE usuarios SET 
                                        NUM_USUARIO = '$numero_control', 
                                        PIN = '$pin'
                                        WHERE PERSONAS_idPERSONAS = $id";
                        
                        if ($conexion->query($sql_usuario) === TRUE) {
                            $conexion->commit();
                            $mensaje = "Alumno actualizado exitosamente.";
                            $modo_edicion = false;
                            
                            // LIMPIAR DATOS DESPUÉS DE EDICIÓN EXITOSA
                            $datos_alumno = null;
                            $id_edicion = 0;
                            
                            // Redirigir para limpiar el POST
                            header("Location: GestionMaestro.php?pagina=gestion&success=1");
                            exit();
                        } else {
                            throw new Exception("Error al actualizar usuario: " . $conexion->error);
                        }
                    } else {
                        throw new Exception("Error al actualizar persona: " . $conexion->error);
                    }
                } else {
                    // MODO REGISTRO: Insertar nuevo registro
                    $sql_persona = "INSERT INTO personas (NOMBRE, PATERNO, MATERNO, CORREO, TELEFONO, ROL, ESTATUS, SEMESTRE, CARRERA) 
                                    VALUES ('$nombre', '$paterno', '$materno', '$correo', '$telefono', '$rol', $estatus, $semestre, '$carrera')";
                    
                    if ($conexion->query($sql_persona) === TRUE) {
                        $id_persona = $conexion->insert_id;
                        
                        // Insertar datos en usuarios
                        $sql_usuario = "INSERT INTO usuarios (NUM_USUARIO, PIN, FECHA_CREACION, PERSONAS_idPERSONAS) 
                                        VALUES ('$numero_control', '$pin', NOW(), $id_persona)";
                        
                        if ($conexion->query($sql_usuario) === TRUE) {
                            $conexion->commit();
                            $mensaje = "Alumno registrado exitosamente.";
                            
                            // LIMPIAR DATOS DESPUÉS DE REGISTRO EXITOSO
                            // Redirigir para limpiar el POST y mostrar formulario vacío
                            header("Location: GestionMaestro.php?pagina=gestion&success=1");
                            exit();
                        } else {
                            throw new Exception("Error al registrar usuario: " . $conexion->error);
                        }
                    } else {
                        throw new Exception("Error al registrar persona: " . $conexion->error);
                    }
                }
            } catch (Exception $e) {
                $conexion->rollback();
                $error = "Error en el proceso: " . $e->getMessage();
            }
        }
    }
    
     // MANTENER MODO EDICIÓN SI HAY ERROR Y ESTAMOS EDITANDO
    if (!empty($error) && $id > 0) {
        $modo_edicion = true;
        $pagina_actual = 'gestion';
        // Recargar datos del alumno para mantener la información en el formulario
        $sql = "SELECT p.*, u.NUM_USUARIO, u.PIN
                FROM personas p 
                INNER JOIN usuarios u ON p.idPERSONAS = u.PERSONAS_idPERSONAS 
                WHERE p.idPERSONAS = $id AND p.ROL = 'alumno'";
        $resultado = $conexion->query($sql);
        if ($resultado->num_rows > 0) {
            $datos_alumno = $resultado->fetch_assoc();
        }
    }
}

// Mostrar mensaje de éxito si viene de redirección
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $mensaje = isset($_GET['action']) && $_GET['action'] == 'edit' ? 
               "Alumno actualizado exitosamente." : 
               "Alumno registrado exitosamente.";
}

// Procesar eliminación de alumno (dar de baja)
if (isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    
    if ($id_eliminar > 0) {
        $sql_persona = "UPDATE personas SET ESTATUS = 0 WHERE idPERSONAS = $id_eliminar AND ROL = 'alumno'";
        
        if ($conexion->query($sql_persona) === TRUE) {
            $mensaje = "Alumno desactivado exitosamente.";
        } else {
            $error = "Error al desactivar: " . $conexion->error;
        }
    }
}

// Procesar solicitud de insumos
if (isset($_GET['solicitud_insumos'])) {
    $pagina_actual = 'solicitud_insumos';
}

// Procesar formulario de solicitud de insumos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['solicitar_insumos'])) {
    $id_laboratorio = isset($_POST['laboratorio']) ? (int)$_POST['laboratorio'] : 0;
    $insumos_solicitados = isset($_POST['insumos']) ? $_POST['insumos'] : [];
    
    // Usar el ID de persona que ya debería estar en sesión
    $id_persona = $_SESSION['idPERSONAS'] ?? 0;
    
    // DEPURACIÓN
    error_log("Solicitud insumos - ID Persona: " . $id_persona . ", ID Laboratorio: " . $id_laboratorio);
    error_log("Insumos solicitados: " . print_r($insumos_solicitados, true));
    
    if ($id_persona <= 0) {
        $error = "Error: No se pudo identificar al usuario maestro. ID: " . $id_persona . ". Por favor, cierre sesión y vuelva a ingresar.";
        $pagina_actual = 'solicitud_insumos';
        error_log("ERROR: ID de persona inválido en solicitud de insumos");
    } else {
        // Validar que se haya seleccionado al menos un insumo
        $insumos_validos = false;
        foreach ($insumos_solicitados as $id_insumo => $cantidad) {
            if ($cantidad > 0) {
                $insumos_validos = true;
                break;
            }
        }
        
        if (!$insumos_validos) {
            $error = "Debe solicitar al menos un insumo con cantidad mayor a 0.";
            $pagina_actual = 'solicitud_insumos';
        } else {
            // Iniciar transacción
            $conexion->begin_transaction();
            
            try {
                // Crear solicitud para cada insumo
                foreach ($insumos_solicitados as $id_insumo => $cantidad) {
                    if ($cantidad > 0) {
                        // Verificar disponibilidad
                        $sql_verificar = "SELECT CANTIDAD_DIS, NOMBRE FROM insumos WHERE idINSUMOS = $id_insumo";
                        $resultado_verificar = $conexion->query($sql_verificar);
                        
                        if ($resultado_verificar && $resultado_verificar->num_rows > 0) {
                            $insumo_data = $resultado_verificar->fetch_assoc();
                            $disponible = $insumo_data['CANTIDAD_DIS'];
                            $nombre_insumo = $insumo_data['NOMBRE'];
                            
                            if ($disponible < $cantidad) {
                                throw new Exception("No hay suficiente cantidad disponible de '$nombre_insumo'. Disponible: $disponible, Solicitado: $cantidad");
                            }
                            
                            // Insertar solicitud
                            $sql_solicitud = "INSERT INTO solicitud_insumos (PERSONAS_idPERSONAS, INSUMOS_idINSUMOS, CANTIDAD, FECHA_SOLICITUD, ESTATUS) 
                                              VALUES ($id_persona, $id_insumo, $cantidad, NOW(), 1)";
                            
                            if (!$conexion->query($sql_solicitud)) {
                                throw new Exception("Error al crear la solicitud: " . $conexion->error);
                            }
                            
                            // Actualizar cantidad disponible
                            $sql_actualizar = "UPDATE insumos SET CANTIDAD_DIS = CANTIDAD_DIS - $cantidad WHERE idINSUMOS = $id_insumo";
                            
                            if (!$conexion->query($sql_actualizar)) {
                                throw new Exception("Error al actualizar inventario: " . $conexion->error);
                            }
                        } else {
                            throw new Exception("Insumo no encontrado: $id_insumo");
                        }
                    }
                }
                
                $conexion->commit();
                $mensaje = "Solicitud de insumos realizada exitosamente.";
                
                // Obtener datos para el ticket - CONSULTA CORREGIDA
                $sql_maestro = "SELECT * FROM personas WHERE idPERSONAS = $id_persona";
                $resultado_maestro = $conexion->query($sql_maestro);
                $maestro = $resultado_maestro->fetch_assoc();
                
                $sql_lab = "SELECT * FROM laboratorios WHERE idLABORATORIOS = $id_laboratorio";
                $resultado_lab = $conexion->query($sql_lab);
                $laboratorio = $resultado_lab->fetch_assoc();
                
                // CONSULTA CORREGIDA - Obtener SOLO las solicitudes recién creadas
                // Primero, obtener el último ID insertado
                $sql_ultimo_id = "SELECT MAX(idSOLICITUD) as ultimo_id FROM solicitud_insumos WHERE PERSONAS_idPERSONAS = $id_persona";
                $resultado_ultimo_id = $conexion->query($sql_ultimo_id);
                $ultimo_id_data = $resultado_ultimo_id->fetch_assoc();
                $ultimo_id = $ultimo_id_data['ultimo_id'] ?? 0;
                
                if ($ultimo_id > 0) {
                    // Obtener todas las solicitudes desde el último ID menos el número de insumos solicitados
                    $num_insumos = count(array_filter($insumos_solicitados, function($cantidad) {
                        return $cantidad > 0;
                    }));
                    
                    $sql_solicitudes = "SELECT s.*, i.NOMBRE, i.DESCRIPCION 
                                        FROM solicitud_insumos s 
                                        INNER JOIN insumos i ON s.INSUMOS_idINSUMOS = i.idINSUMOS
                                        WHERE s.idSOLICITUD > ($ultimo_id - $num_insumos)
                                        AND s.PERSONAS_idPERSONAS = $id_persona
                                        ORDER BY s.idSOLICITUD DESC";
                } else {
                    // Fallback: obtener las últimas solicitudes de los últimos 5 minutos
                    $sql_solicitudes = "SELECT s.*, i.NOMBRE, i.DESCRIPCION 
                                        FROM solicitud_insumos s 
                                        INNER JOIN insumos i ON s.INSUMOS_idINSUMOS = i.idINSUMOS
                                        WHERE s.PERSONAS_idPERSONAS = $id_persona 
                                        AND s.FECHA_SOLICITUD >= NOW() - INTERVAL 5 MINUTE
                                        ORDER BY s.idSOLICITUD DESC";
                }
                
                $resultado_solicitudes = $conexion->query($sql_solicitudes);
                
                // DEPURACIÓN: Verificar qué solicitudes se obtuvieron
                error_log("Consulta de solicitudes: " . $sql_solicitudes);
                error_log("Número de solicitudes encontradas: " . ($resultado_solicitudes ? $resultado_solicitudes->num_rows : 0));
                
                if ($resultado_solicitudes && $resultado_solicitudes->num_rows > 0) {
                    $resultado_solicitudes->data_seek(0);
                    while ($solicitud = $resultado_solicitudes->fetch_assoc()) {
                        error_log("Solicitud encontrada: " . $solicitud['NOMBRE'] . " - Cantidad: " . $solicitud['CANTIDAD']);
                    }
                    $resultado_solicitudes->data_seek(0); // Reset pointer
                }
                
                // Guardar datos del ticket en variables para mostrar
                $ticket_data = [
                    'maestro' => $maestro,
                    'laboratorio' => $laboratorio,
                    'solicitudes' => $resultado_solicitudes,
                    'fecha' => date('d/m/Y H:i:s'),
                    'numero_ticket' => 'TKT-' . date('YmdHis') . '-' . $id_laboratorio
                ];
                
                // Mostrar ticket en la misma página
                $mostrar_ticket = true;
                
            } catch (Exception $e) {
                $conexion->rollback();
                $error = $e->getMessage();
                $pagina_actual = 'solicitud_insumos';
                error_log("ERROR en solicitud insumos: " . $e->getMessage());
            }
        }
    }
}

// Consultar alumnos registrados (solo activos)
$sql_alumnos = "SELECT p.*, u.NUM_USUARIO, u.FECHA_CREACION
                 FROM personas p 
                 INNER JOIN usuarios u ON p.idPERSONAS = u.PERSONAS_idPERSONAS 
                 WHERE p.ESTATUS = 1 AND p.ROL = 'alumno'
                 ORDER BY p.idPERSONAS DESC";
$resultado_alumnos = $conexion->query($sql_alumnos);

// Consultar estadísticas para el dashboard (solo alumnos)
$sql_estadisticas = "SELECT 
                     COUNT(*) as total_alumnos,
                     SUM(CASE WHEN p.ESTATUS = 1 THEN 1 ELSE 0 END) as alumnos_activos,
                     SUM(CASE WHEN p.ESTATUS = 0 THEN 1 ELSE 0 END) as alumnos_inactivos
                     FROM personas p 
                     INNER JOIN usuarios u ON p.idPERSONAS = u.PERSONAS_idPERSONAS
                     WHERE p.ROL = 'alumno'";
$resultado_estadisticas = $conexion->query($sql_estadisticas);
$estadisticas = $resultado_estadisticas->fetch_assoc();


// Consultar laboratorios disponibles - CON DEPURACIÓN
$sql_laboratorios = "SELECT idLABORATORIOS, NOM_LAB FROM laboratorios ORDER BY NOM_LAB";
$resultado_laboratorios = $conexion->query($sql_laboratorios);

// Depuración: ver qué laboratorios se están obteniendo
echo "<!-- DEBUG: Laboratorios obtenidos -->";
if ($resultado_laboratorios && $resultado_laboratorios->num_rows > 0) {
    $resultado_laboratorios->data_seek(0);
    while ($lab = $resultado_laboratorios->fetch_assoc()) {
        echo "<!-- Lab: ID=" . $lab['idLABORATORIOS'] . ", Nombre=" . $lab['NOM_LAB'] . " -->";
    }
    // Reiniciar el puntero para el select
    $resultado_laboratorios->data_seek(0);
} else {
    echo "<!-- DEBUG: No se encontraron laboratorios -->";
}


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión - <?php echo ucfirst($pagina_actual); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styleRegistroMaestro.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar de navegación -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-chalkboard-teacher"></i> Panel de Control</h2>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($_SESSION['nombre']); ?></h3>
                    <p>Maestro</p>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item <?php echo $pagina_actual == 'dashboard' ? 'active' : ''; ?>">
                        <a href="GestionMaestro.php?pagina=dashboard">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $pagina_actual == 'gestion' ? 'active' : ''; ?>">
                        <a href="GestionMaestro.php?pagina=gestion">
                            <i class="fas fa-user-graduate"></i>
                            <span>Gestión Alumnos</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $pagina_actual == 'solicitud_insumos' ? 'active' : ''; ?>">
                        <a href="GestionMaestro.php?solicitud_insumos=1">
                            <i class="fas fa-box-open"></i>
                            <span>Pedir Insumos</span>
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
                        if ($pagina_actual == 'dashboard') echo 'Dashboard Maestro';
                        elseif ($pagina_actual == 'gestion') echo 'Gestión de Alumnos';
                        elseif ($pagina_actual == 'solicitud_insumos') echo 'Solicitud de Insumos';
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
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="card-info">
                                <h3>Total Alumnos</h3>
                                <p><?php echo $estadisticas['total_alumnos']; ?></p>
                            </div>
                        </div>
                        
                        <div class="summary-card">
                            <div class="card-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="card-info">
                                <h3>Alumnos Activos</h3>
                                <p><?php echo $estadisticas['alumnos_activos']; ?></p>
                            </div>
                        </div>
                        
                        <div class="summary-card">
                            <div class="card-icon">
                                <i class="fas fa-user-times"></i>
                            </div>
                            <div class="card-info">
                                <h3>Alumnos Inactivos</h3>
                                <p><?php echo $estadisticas['alumnos_inactivos']; ?></p>
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
                                        <p>Nuevo alumno registrado</p>
                                        <span>Hace 2 horas</span>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-edit activity-icon"></i>
                                    <div class="activity-details">
                                        <p>Información de alumno actualizada</p>
                                        <span>Hace 5 horas</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="section quick-actions">
                            <h2>Acciones Rápidas</h2>
                            <div class="action-buttons">
                                <a href="GestionMaestro.php?pagina=gestion" class="action-btn">
                                    <i class="fas fa-user-graduate"></i>
                                    <span>Gestionar Alumnos</span>
                                </a>
                                <a href="GestionMaestro.php?pagina=gestion" class="action-btn">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Registrar Alumno</span>
                                </a>
                                <a href="GestionMaestro.php?solicitud_insumos=1" class="action-btn">
                                    <i class="fas fa-box-open"></i>
                                    <span>Solicitar Insumos</span>
                                </a>
                            </div>
                        </div>
                    </div>
                
                <!-- Contenido de Gestión de Alumnos -->
                <?php elseif ($pagina_actual == 'gestion'): ?>
                    <!-- Formulario de registro/edición -->
                        <div class="form-section">
                            <h2><?php echo $modo_edicion ? 'Editar Alumno' : 'Registrar Nuevo Alumno'; ?></h2>
                            
                            <form method="POST" action="GestionMaestro.php?pagina=gestion<?php echo $modo_edicion ? '&editar=' . $id_edicion : ''; ?>" class="registro-form">
                                <?php if ($modo_edicion): ?>
                                    <input type="hidden" name="id" value="<?php echo $id_edicion; ?>">
                                <?php endif; ?>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="nombre">Nombre *</label>
                                        <input type="text" id="nombre" name="nombre" 
                                            value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ($modo_edicion ? htmlspecialchars($datos_alumno['NOMBRE']) : ''); ?>" 
                                            pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+" 
                                            maxlength="30"
                                            required
                                            title="Solo se permiten letras y espacios, máximo 30 caracteres">
                                        <small class="form-text">Máximo 30 caracteres, solo letras y espacios</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="paterno">Apellido Paterno *</label>
                                        <input type="text" id="paterno" name="paterno" 
                                            value="<?php echo isset($_POST['paterno']) ? htmlspecialchars($_POST['paterno']) : ($modo_edicion ? htmlspecialchars($datos_alumno['PATERNO']) : ''); ?>" 
                                            pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+" 
                                            maxlength="30"
                                            required
                                            title="Solo se permiten letras y espacios, máximo 30 caracteres">
                                        <small class="form-text">Máximo 30 caracteres, solo letras y espacios</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="materno">Apellido Materno</label>
                                        <input type="text" id="materno" name="materno" 
                                            value="<?php echo isset($_POST['materno']) ? htmlspecialchars($_POST['materno']) : ($modo_edicion ? htmlspecialchars($datos_alumno['MATERNO']) : ''); ?>" 
                                            pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+"
                                            maxlength="30"
                                            title="Solo se permiten letras y espacios, máximo 30 caracteres">
                                        <small class="form-text">Máximo 30 caracteres, solo letras y espacios</small>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="correo">Correo Electrónico</label>
                                        <input type="email" id="correo" name="correo" 
                                            value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ($modo_edicion ? htmlspecialchars($datos_alumno['CORREO']) : ''); ?>" 
                                            maxlength="200"
                                            title="El correo debe ser válido, máximo 200 caracteres">
                                        <small class="form-text">Máximo 200 caracteres</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="telefono">Teléfono</label>
                                        <input type="tel" id="telefono" name="telefono" 
                                            value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ($modo_edicion ? htmlspecialchars($datos_alumno['TELEFONO']) : ''); ?>" 
                                            pattern="[0-9]{10}" 
                                            maxlength="10"
                                            title="El número de teléfono debe contener exactamente 10 dígitos">
                                        <small class="form-text">Exactamente 10 dígitos</small>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="numero_control">Número de Control *</label>
                                        <input type="text" id="numero_control" name="numero_control" 
                                            value="<?php echo isset($_POST['numero_control']) ? htmlspecialchars($_POST['numero_control']) : ($modo_edicion ? htmlspecialchars($datos_alumno['NUM_USUARIO']) : ''); ?>" 
                                            maxlength="8" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="pin">PIN * (4 dígitos)</label>
                                        <input type="text" id="pin" name="pin" 
                                            value="<?php echo isset($_POST['pin']) ? htmlspecialchars($_POST['pin']) : ($modo_edicion ? htmlspecialchars($datos_alumno['PIN']) : ''); ?>" 
                                            maxlength="4" pattern="\d{4}" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="semestre">Semestre *</label>
                                        <select id="semestre" name="semestre" required>
                                            <option value="">Seleccione...</option>
                                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                                <option value="<?php echo $i; ?>" 
                                                    <?php 
                                                    $semestre_selected = isset($_POST['semestre']) ? $_POST['semestre'] : ($modo_edicion && isset($datos_alumno['SEMESTRE']) ? $datos_alumno['SEMESTRE'] : 1);
                                                    echo $semestre_selected == $i ? 'selected' : ''; 
                                                    ?>>
                                                    <?php echo $i; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="carrera">Carrera *</label>
                                        <select id="carrera" name="carrera" required>
                                            <option value="">Seleccione...</option>
                                            <?php 
                                            $carreras = ['Sistemas', 'Mecatronica', 'Electronica'];
                                            $carrera_selected = isset($_POST['carrera']) ? $_POST['carrera'] : ($modo_edicion && isset($datos_alumno['CARRERA']) ? $datos_alumno['CARRERA'] : 'Sistemas');
                                            foreach ($carreras as $carrera_option): 
                                            ?>
                                                <option value="<?php echo $carrera_option; ?>" 
                                                    <?php echo $carrera_selected == $carrera_option ? 'selected' : ''; ?>>
                                                    <?php echo $carrera_option; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <input type="hidden" name="rol" value="alumno">
                                    
                                    <?php if ($modo_edicion): ?>
                                        <div class="form-group">
                                            <label for="estatus">Estatus *</label>
                                            <select id="estatus" name="estatus" required>
                                                <?php 
                                                $estatus_selected = isset($_POST['estatus']) ? $_POST['estatus'] : ($modo_edicion && isset($datos_alumno['ESTATUS']) ? $datos_alumno['ESTATUS'] : 1);
                                                ?>
                                                <option value="1" <?php echo $estatus_selected == 1 ? 'selected' : ''; ?>>Activo</option>
                                                <option value="0" <?php echo $estatus_selected == 0 ? 'selected' : ''; ?>>Inactivo</option>
                                            </select>
                                        </div>
                                    <?php else: ?>
                                        <input type="hidden" name="estatus" value="1">
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn-registrar">
                                        <?php echo $modo_edicion ? 'Actualizar Alumno' : 'Registrar Alumno'; ?>
                                    </button>
                                    
                                    <?php if ($modo_edicion): ?>
                                        <a href="GestionMaestro.php?pagina=gestion" class="btn-cancelar">Cancelar</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    
                    <!-- Lista de alumnos registrados -->
                    <div class="table-section">
                        <h2>Alumnos Registrados</h2>
                        
                        <!-- Barra de búsqueda y filtros -->
                        <div class="content-toolbar">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Buscar alumnos..." id="buscarAlumnos">
                            </div>
                        </div>
                        
                        <!-- Tabla de alumnos -->
                        <div class="table-container">
                            <?php if ($resultado_alumnos->num_rows > 0): ?>
                                <table class="data-table" id="tablaAlumnos">
                                    <thead>
                                        <tr>
                                            <th>Nombre Completo</th>
                                            <th>Correo</th>
                                            <th>Teléfono</th>
                                            <th>Número de Control</th>
                                            <th>Semestre</th>
                                            <th>Carrera</th>
                                            <th>Fecha Registro</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($fila = $resultado_alumnos->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($fila['NOMBRE'] . ' ' . $fila['PATERNO'] . ' ' . $fila['MATERNO']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['CORREO']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['TELEFONO']); ?></td>
                                                <td><?php echo htmlspecialchars($fila['NUM_USUARIO']); ?></td>
                                                <td><?php echo isset($fila['SEMESTRE']) ? $fila['SEMESTRE'] : 'N/A'; ?></td>
                                                <td><?php echo isset($fila['CARRERA']) ? htmlspecialchars($fila['CARRERA']) : 'N/A'; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($fila['FECHA_CREACION'])); ?></td>
                                                <td class="actions">
                                                    <a href="GestionMaestro.php?pagina=gestion&editar=<?php echo $fila['idPERSONAS']; ?>" class="btn-action edit" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="GestionMaestro.php?pagina=gestion&eliminar=<?php echo $fila['idPERSONAS']; ?>" class="btn-action delete" title="Dar de baja" onclick="return confirm('¿Estás seguro de dar de baja a este alumno?')">
                                                        <i class="fas fa-user-times"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-user-graduate"></i>
                                    <h3>No hay alumnos registrados</h3>
                                    <p>Comienza registrando el primer alumno en el sistema</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                                <!-- Contenido de Solicitud de Insumos -->
                <!-- Contenido de Solicitud de Insumos -->
                            <?php elseif ($pagina_actual == 'solicitud_insumos'): ?>
                                <div class="form-section">
                                    <h2>Solicitud de Insumos</h2>
                                    
                                    <?php
                                    // Consultar laboratorios disponibles
                                    $sql_laboratorios = "SELECT * FROM laboratorios ORDER BY NOM_LAB";
                                    $resultado_laboratorios = $conexion->query($sql_laboratorios);
                                    ?>
                                    
                                    <form method="POST" action="GestionMaestro.php?pagina=solicitud_insumos" id="formSolicitudInsumos">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="laboratorio">Laboratorio *</label>
                                                <select id="laboratorio" name="laboratorio" required>
                                                    <option value="">Seleccione un laboratorio...</option>
                                                    <?php 
                                                    if ($resultado_laboratorios && $resultado_laboratorios->num_rows > 0) {
                                                        $resultado_laboratorios->data_seek(0);
                                                        while ($lab = $resultado_laboratorios->fetch_assoc()) {
                                                            echo '<option value="' . $lab['idLABORATORIOS'] . '">' . 
                                                                htmlspecialchars($lab['NOM_LAB']) . '</option>';
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div id="insumos-container" style="display: none; margin-top: 20px;">
                                            <h3>Insumos Disponibles</h3>
                                            <div class="table-container">
                                                <table class="data-table" id="tablaInsumos">
                                                    <thead>
                                                        <tr>
                                                            <th>Insumo</th>
                                                            <th>Descripción</th>
                                                            <th>Cantidad Disponible</th>
                                                            <th>Cantidad a Solicitar</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="lista-insumos">
                                                        <!-- Los insumos se cargarán aquí dinámicamente -->
                                                    </tbody>
                                                </table>
                                            </div>
                                            
                                            <div class="form-actions">
                                                <button type="submit" name="solicitar_insumos" class="btn-registrar">
                                                    <i class="fas fa-file-alt"></i> Generar Solicitud
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                    
                                    <div id="mensaje-carga" style="display: none; text-align: center; padding: 20px;">
                                        <i class="fas fa-spinner fa-spin"></i> Cargando insumos...
                                    </div>
                                </div>
                                
                                <!-- TICKET - Se muestra después de generar la solicitud -->
                    <?php if (isset($mostrar_ticket) && $mostrar_ticket): ?>
                        <?php 
                        // Incluir el template del ticket
                        require_once 'ticket_template.php';
                        echo generarHTMLTicket($ticket_data);
                        ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="js/scriptRegistroMaestro.js"></script>
</body>
</html>
<?php
// Cerrar conexión
$conexion->close();
?>