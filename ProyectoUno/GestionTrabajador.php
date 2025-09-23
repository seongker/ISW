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
$modo_edicion = false;
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

// Procesar formulario de registro/edición
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
    
    // Validaciones básicas
    if (empty($nombre) || empty($paterno) || empty($numero_control) || empty($pin)) {
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
        // VALIDACIÓN: Número de control único (excepto para el registro actual en edición)
        $sql_check_numero = "SELECT COUNT(*) as total FROM usuarios WHERE NUM_USUARIO = '$numero_control'";
        if ($id > 0) {
            $sql_check_numero .= " AND PERSONAS_idPERSONAS != $id";
        }
        $result_numero = $conexion->query($sql_check_numero);
        $existe_numero = $result_numero->fetch_assoc()['total'] > 0;
        
        // VALIDACIÓN: Teléfono único (excepto para el registro actual en edición)
        $sql_check_telefono = "SELECT COUNT(*) as total FROM personas WHERE TELEFONO = '$telefono' AND TELEFONO != ''";
        if ($id > 0) {
            $sql_check_telefono .= " AND idPERSONAS != $id";
        }
        $result_telefono = $conexion->query($sql_check_telefono);
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
                                    NOMBRE = '$nombre', 
                                    PATERNO = '$paterno', 
                                    MATERNO = '$materno', 
                                    CORREO = '$correo', 
                                    TELEFONO = '$telefono', 
                                    ROL = '$rol',
                                    ESTATUS = $estatus 
                                    WHERE idPERSONAS = $id";
                    
                    if ($conexion->query($sql_persona) === TRUE) {
                        // SOLO ACTUALIZAR DATOS BÁSICOS EN USUARIOS (SIN ESTATUS)
                        $sql_usuario = "UPDATE usuarios SET 
                                        NUM_USUARIO = '$numero_control', 
                                        PIN = '$pin'
                                        WHERE PERSONAS_idPERSONAS = $id";
                        
                        if ($conexion->query($sql_usuario) === TRUE) {
                            $conexion->commit();
                            $mensaje = "Trabajador actualizado exitosamente.";
                            $modo_edicion = false;
                        } else {
                            throw new Exception("Error al actualizar usuario: " . $conexion->error);
                        }
                    } else {
                        throw new Exception("Error al actualizar persona: " . $conexion->error);
                    }
                } else {
                    // MODO REGISTRO: Insertar nuevo registro
                    $sql_persona = "INSERT INTO personas (NOMBRE, PATERNO, MATERNO, CORREO, TELEFONO, ROL, ESTATUS) 
                                    VALUES ('$nombre', '$paterno', '$materno', '$correo', '$telefono', '$rol', $estatus)";
                    
                    if ($conexion->query($sql_persona) === TRUE) {
                        $id_persona = $conexion->insert_id;
                        
                        // SOLO INSERTAR DATOS BÁSICOS EN USUARIOS (SIN ESTATUS)
                        $sql_usuario = "INSERT INTO usuarios (NUM_USUARIO, PIN, FECHA_CREACION, PERSONAS_idPERSONAS) 
                                        VALUES ('$numero_control', '$pin', NOW(), $id_persona)";
                        
                        if ($conexion->query($sql_usuario) === TRUE) {
                            $conexion->commit();
                            $mensaje = "Trabajador registrado exitosamente.";
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

// Consultar trabajadores registrados (solo activos)
$sql_trabajadores = "SELECT p.*, u.NUM_USUARIO, u.FECHA_CREACION
                     FROM personas p 
                     INNER JOIN usuarios u ON p.idPERSONAS = u.PERSONAS_idPERSONAS 
                     WHERE p.ESTATUS = 1
                     ORDER BY p.idPERSONAS DESC";
$resultado_trabajadores = $conexion->query($sql_trabajadores);

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

// Cerrar conexión
$conexion->close();
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión - <?php echo ucfirst($pagina_actual); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styleGestores.css">
    
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
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="card-info">
                                <h3>Maestros</h3>
                                <p><?php echo $estadisticas['maestros']; ?></p>
                            </div>
                        </div>
                        
                        <div class="summary-card">
                            <div class="card-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="card-info">
                                <h3>Activos</h3>
                                <p><?php echo $estadisticas['activos']; ?></p>
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
                                    <i class="fas fa-edit activity-icon"></i>
                                    <div class="activity-details">
                                        <p>Información de trabajador actualizada</p>
                                        <span>Hace 5 horas</span>
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
                                <a href="GestionTrabajador.php?pagina=gestion" class="action-btn">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Registrar Trabajador</span>
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
                            <?php if ($modo_edicion): ?>
                                <input type="hidden" name="id" value="<?php echo $id_edicion; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nombre">Nombre *</label>
                                    <input type="text" id="nombre" name="nombre" 
                                           value="<?php echo $modo_edicion ? htmlspecialchars($datos_trabajador['NOMBRE']) : ''; ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="paterno">Apellido Paterno *</label>
                                    <input type="text" id="paterno" name="paterno" 
                                           value="<?php echo $modo_edicion ? htmlspecialchars($datos_trabajador['PATERNO']) : ''; ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="materno">Apellido Materno</label>
                                    <input type="text" id="materno" name="materno" 
                                           value="<?php echo $modo_edicion ? htmlspecialchars($datos_trabajador['MATERNO']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="correo">Correo Electrónico</label>
                                    <input type="email" id="correo" name="correo" 
                                           value="<?php echo $modo_edicion ? htmlspecialchars($datos_trabajador['CORREO']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="telefono">Teléfono</label>
                                    <input type="tel" id="telefono" name="telefono" 
                                           value="<?php echo $modo_edicion ? htmlspecialchars($datos_trabajador['TELEFONO']) : ''; ?>">
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="js/scriptRegistroTrabajador.js"></script>
</body>
</html>