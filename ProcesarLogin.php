<?php
session_start();
header('Content-Type: application/json');

// Incluir la conexión
require_once 'Conexion/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && isset($_POST['password'])) {
    $numero_control = $_POST['username'];
    $pin = $_POST['password'];
    
    // Limpiar los datos (usando la función de conexion.php)
    $numero_control_limpio = limpiarDatos($numero_control, $conexion);
    $pin_limpio = limpiarDatos($pin, $conexion);
    
    // Consulta para verificar usuario
    $sql = "SELECT p.*, u.NUM_USUARIO, u.PIN 
            FROM personas p 
            INNER JOIN usuarios u ON p.idPERSONAS = u.PERSONAS_idPERSONAS 
            WHERE u.NUM_USUARIO = '$numero_control_limpio' AND p.ESTATUS = 1";
    
    $resultado = $conexion->query($sql);
    
    if ($resultado && $resultado->num_rows > 0) {
        // Usuario existe, verificar PIN
        $usuario = $resultado->fetch_assoc();
        
        if ($pin_limpio === $usuario['PIN']) {
            // Credenciales correctas - GUARDAR TODOS LOS DATOS NECESARIOS
            $_SESSION['numero_control'] = $numero_control_limpio;
            $_SESSION['loggedin'] = true;
            $_SESSION['rol'] = $usuario['ROL'];
            $_SESSION['nombre'] = $usuario['NOMBRE'] . ' ' . $usuario['PATERNO'] . ' ' . $usuario['MATERNO'];
            $_SESSION['id_persona'] = $usuario['idPERSONAS'];
            
            // ESTAS SON LAS LÍNEAS CRÍTICAS QUE FALTAN:
            $_SESSION['idPERSONAS'] = $usuario['idPERSONAS']; // ← ESTA ES LA MÁS IMPORTANTE
            $_SESSION['id_usuario'] = $usuario['idPERSONAS']; // ← Y ESTA TAMBIÉN
            $_SESSION['correo'] = $usuario['CORREO']; // ← PARA RESPALDO
            
            echo json_encode([
                'success' => true,
                'redirect' => 'DashboardPrincipal.php'
            ]);
            exit();
        } else {
            // PIN incorrecto
            echo json_encode([
                'success' => false,
                'error_type' => 'password',
                'message' => 'El NIP es incorrecto'
            ]);
            exit();
        }
    } else {
        // Usuario no existe
        echo json_encode([
            'success' => false,
            'error_type' => 'username',
            'message' => 'El número de control es incorrecto'
        ]);
        exit();
    }
} else {
    // Solicitud incorrecta
    echo json_encode([
        'success' => false,
        'error_type' => 'general',
        'message' => 'Solicitud inválida'
    ]);
    exit();
}
?>