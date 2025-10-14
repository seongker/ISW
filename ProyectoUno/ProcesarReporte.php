<?php
require_once 'Conexion/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipo = $_POST['tipo_incidencia'];
    $elabora = $_POST['elabora'];
    $reporta = $_POST['reporta'];
    $responsable = $_POST['responsable'];
    $descripcion = $_POST['descripcion'];
    $estatus = $_POST['estatus'];

    $sql = "INSERT INTO reportes (tipo_incidencia, elabora, reporta, responsable, descripcion, estatus)
            VALUES ('$tipo', '$elabora', '$reporta', '$responsable', '$descripcion', '$estatus')";

    if ($conexion->query($sql) === TRUE) {
        header("Location: GestionReportes.php");
        exit();
    } else {
        echo "Error al guardar: " . $conexion->error;
    }
}
?>
