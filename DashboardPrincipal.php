<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

// DEPURACIÓN: Verificar que tenemos todos los datos necesarios
if (!isset($_SESSION['idPERSONAS']) && isset($_SESSION['id_persona'])) {
    // Sincronizar id_persona con idPERSONAS si es necesario
    $_SESSION['idPERSONAS'] = $_SESSION['id_persona'];
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