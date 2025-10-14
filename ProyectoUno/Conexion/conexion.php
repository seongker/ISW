<?php
// Conexión a la base de datos MySQL
$servidor = "localhost";
$usuario = "root";
$password = "";
$basedatos = "isw";

// Crear conexión
$conexion = new mysqli($servidor, $usuario, $password, $basedatos);

// Verificar conexión
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Establecer el conjunto de caracteres a utf8
$conexion->set_charset("utf8");

// Función para limpiar y validar datos de entrada
function limpiarDatos($datos, $conexion) {
    $datos = trim($datos);
    $datos = stripslashes($datos);
    $datos = htmlspecialchars($datos);
    $datos = $conexion->real_escape_string($datos);
    return $datos;
}

// Función para validar usuario (mantener por compatibilidad si es necesaria)
function validarUsuario($numero_control, $pin, $conexion) {
    // Limpiar los datos
    $numero_control = limpiarDatos($numero_control, $conexion);
    $pin = limpiarDatos($pin, $conexion);
    
    // Consulta CORREGIDA - sin u.ESTATUS
    $sql = "SELECT p.*, u.NUM_USUARIO, u.PIN 
            FROM personas p 
            INNER JOIN usuarios u ON p.idPERSONAS = u.PERSONAS_idPERSONAS 
            WHERE u.NUM_USUARIO = '$numero_control' AND u.PIN = '$pin' AND p.ESTATUS = 1";
    
    $resultado = $conexion->query($sql);
    
    if ($resultado && $resultado->num_rows > 0) {
        // Usuario válido, guardar datos en sesión
        $usuario = $resultado->fetch_assoc();
        session_start();
        $_SESSION['numero_control'] = $numero_control;
        $_SESSION['loggedin'] = true;
        $_SESSION['rol'] = $usuario['ROL'];
        $_SESSION['nombre'] = $usuario['NOMBRE'] . ' ' . $usuario['PATERNO'] . ' ' . $usuario['MATERNO'];
        $_SESSION['id_persona'] = $usuario['idPERSONAS'];
        
        return true;
    } else {
        // Credenciales incorrectas
        return false;
    }
}

// ELIMINAR O COMENTAR ESTA PARTE - ya no es necesaria
/*
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && isset($_POST['password'])) {
    $numero_control = $_POST['username'];
    $pin = $_POST['password'];
    
    if (validarUsuario($numero_control, $pin, $conexion)) {
        // Inicio de sesión exitoso
        // Redirigir al dashboard principal
        header("Location: DashboardPrincipal.php");
        exit();
    } else {
        // Credenciales incorrectas
        $error_message = "Número de control o PIN incorrectos";
        session_start();
        $_SESSION['error_login'] = $error_message;
        header("Location: index.php");
        exit();
    }
}
*/
?>