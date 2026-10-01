<?php 
header("Content-Type: application/json");
include('conexion.php');
if (!$conexion) {
    echo json_encode([
        "error" => "Error de conexión"
    ]);
    exit;
}
$sql = "SELECT * FROM usuarios";
$resultado = mysqli_query($conexion, $sql);

$usuarios = [];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $usuarios[] = $fila;
}
echo json_encode($usuarios);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
<script src="script/usuarios.js" defer></script>
</body>
</html>