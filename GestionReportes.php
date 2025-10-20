<?php
session_start();

// Verificar sesión activa
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'trabajador') {
    header("Location: index.php");
    exit();
}

require_once "Conexion/conexion.php";

$mensaje = "";
$error = "";
$pagina_actual = "reportes";

// Registrar nuevo reporte
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['registrar_reporte'])) {
    $tipo = $_POST['tipo_incidencia'];
    $descripcion = $_POST['descripcion'];
    $elabora = $_POST['elabora'];
    $reporta = $_POST['reporta'];
    $responsable = $_POST['responsable'];
    $estatus = $_POST['estatus'];
    $accion = $_POST['accion'];

    $stmt = $conexion->prepare("INSERT INTO reportes 
        (tipo_incidencia, descripcion, elabora, reporta, responsable, estatus, accion, fecha)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->bind_param("sssssss", $tipo, $descripcion, $elabora, $reporta, $responsable, $estatus, $accion);

    if ($stmt->execute()) {
        $mensaje = "✅ Reporte registrado correctamente.";
    } else {
        $error = "❌ Error al registrar el reporte: " . $conexion->error;
    }
    $stmt->close();
}

// Eliminar reporte
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $conexion->query("DELETE FROM reportes WHERE idReporte = $id");
    header("Location: GestionReportes.php");
    exit();
}

// Consultar reportes existentes
$reportes = $conexion->query("SELECT * FROM reportes ORDER BY fecha DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Reportes</title>
    <link rel="stylesheet" href="css/styleGestores.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-cogs"></i> Panel de Control</h2>
        </div>

        <div class="user-info">
            <div class="user-avatar"><i class="fas fa-user-circle"></i></div>
            <div class="user-details">
                <h3><?php echo htmlspecialchars($_SESSION['nombre']); ?></h3>
                <p>Trabajador</p>
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li class="nav-item <?php echo $pagina_actual == 'dashboard' ? 'active' : ''; ?>">
                    <a href="DashboardPrincipal.php?pagina=dashboard">
                        <i class="fas fa-home"></i><span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item <?php echo $pagina_actual == 'gestion' ? 'active' : ''; ?>">
                    <a href="GestionTrabajador.php?pagina=gestion">
                        <i class="fas fa-users-cog"></i><span>Gestión Trabajadores</span>
                    </a>
                </li>
                <li class="nav-item <?php echo $pagina_actual == 'reportes' ? 'active' : ''; ?>">
                    <a href="GestionReportes.php?pagina=reportes">
                        <i class="fas fa-file-alt"></i><span>Gestión Reportes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Cerrar Sesión</span></a>
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
                <h1>Gestión de Reportes</h1>
            </div>
            <div class="header-right">
                <span><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            </div>
        </header>

        <div class="content">
            <!-- Mensajes -->
            <?php if (!empty($mensaje)): ?>
                <div class="alert success"><i class="fas fa-check-circle"></i> <?php echo $mensaje; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Formulario -->
            <div class="card form-card">
                <div class="card-header">
                    <h2><i class="fas fa-clipboard-list"></i> Registrar Nuevo Reporte</h2>
                </div>
                <div class="card-body">
<form method="POST">
    <div class="form-grid">
        <div class="form-group">
            <label>Tipo de Incidencia:</label>
            <select name="tipo_incidencia" required>
                <option value="">Selecciona una opción</option>
                <option value="Pérdida de material">Pérdida de material</option>
                <option value="Daño de equipo">Daño de equipo</option>
                <option value="Otro">Otro</option>
            </select>
        </div>

        <div class="form-group">
            <label>Estatus:</label>
            <select name="estatus" required>
                <option value="">Selecciona una opción</option>
                <option value="Pendiente">Pendiente</option>
                <option value="En revisión">En revisión</option>
                <option value="Resuelto">Resuelto</option>
            </select>
        </div>

        <div class="form-group">
            <label>Elaboró:</label>
            <select name="elabora" required>
                <option value="">Selecciona una opción</option>
                <option value="Vicente">Vicente</option>
                <option value="Encargado 1">Encargado 1</option>
                <option value="Encargado 2">Encargado 2</option>
                <option value="Encargado 3">Encargado 3</option>
            </select>
        </div>

        <div class="form-group">
            <label>Reportó:</label>
            <input type="text" name="reporta" required>
        </div>

        <div class="form-group">
            <label>Responsable (quien dañó o perdió):</label>
            <input type="text" name="responsable" required>
        </div>

        <div class="form-group">
            <label>Acción tomada:</label>
            <input type="text" name="accion" required>
        </div>
    </div>

    <div class="form-group full-width">
        <label for="descripcion">Descripción (opcional):</label>
        <input type="text" id="descripcion" name="descripcion">
    </div>

    <div class="form-actions">
        <button type="submit" name="registrar_reporte" class="btn-agregar">
            <i class="fas fa-plus-circle"></i> Registrar Reporte
        </button>
    </div>
</form>
                </div>
            </div>

            <!-- Tabla de reportes -->
            <div class="card table-card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Reportes Registrados</h2>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Elaboró</th>
                                <th>Reportó</th>
                                <th>Responsable</th>
                                <th>Estatus</th>
                                <th>Acción</th>
                                <th>Fecha</th>
                                <th>Eliminar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $reportes->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['idReporte']; ?></td>
                                    <td><?php echo $row['tipo_incidencia']; ?></td>
                                    <td><?php echo $row['descripcion']; ?></td>
                                    <td><?php echo $row['elabora']; ?></td>
                                    <td><?php echo $row['reporta']; ?></td>
                                    <td><?php echo $row['responsable']; ?></td>
                                    <td><?php echo $row['estatus']; ?></td>
                                    <td><?php echo $row['accion']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></td>
                                    <td>
                                        <a href="GestionReportes.php?eliminar=<?php echo $row['idReporte']; ?>" 
                                           class="btn-eliminar"
                                           onclick="return confirm('¿Deseas eliminar este reporte?');">
                                           <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
