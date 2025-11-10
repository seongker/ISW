<?php
session_start();
header('Content-Type: application/json');

// Verificar si el usuario ha iniciado sesión y es trabajador
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'trabajador') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Incluir archivo de conexión
require_once 'Conexion/conexion.php';

$input = json_decode(file_get_contents('php://input'), true);
$id_insumo = isset($input['id']) ? (int)$input['id'] : 0;

if ($id_insumo > 0) {
    // Baja lógica - marcar como inactivo
    $sql = "UPDATE insumos SET ESTATUS = 0 WHERE idINSUMOS = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_insumo);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Insumo dado de baja correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al dar de baja: ' . $conexion->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'ID de insumo inválido'  ]);
}

$conexion->close();
?>