<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

// Redirigir según el rol
switch ($_SESSION['rol']) {
    case 'alumno':
        header("Location: GestionAlumno.php");
        break;
    case 'maestro':
        header("Location: GestionMaestro.php");
        break;
    case 'trabajador':
        header("Location: GestionTrabajador.php");
        break;
    default:
        // Rol no reconocido, cerrar sesión
        session_destroy();
        header("Location: index.php?error=rol_no_valido");
        break;
}
exit();
?>