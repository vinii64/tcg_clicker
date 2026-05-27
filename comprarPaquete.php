<?php 
include('conexion.php');
session_start();

// Validamos que el usuario tenga una sesión activa
if (!isset($_SESSION['id'])) {
    header('Content-Type: application/json');
    echo json_encode(array('status' => 'error', 'mensaje' => 'Sesión no iniciada'));
    exit();
}

$id_user = $_SESSION['id'];

// Seleccionamos los puntos actuales del usuario
$query2 = "SELECT puntos FROM usuarios WHERE id = '$id_user'";
$puntosDB = mysqli_query($conexion, $query2);
$resultado = mysqli_fetch_assoc($puntosDB);

$respuesta = array();

// Verificamos si tiene puntos suficientes para el paquete (costo: 10)
if ($resultado['puntos'] >= 10) {
    $puntosActuales = $resultado['puntos'] - 10;
    $query = "UPDATE usuarios SET puntos = '$puntosActuales' WHERE id = '$id_user'";
    $actualizacionPuntos = mysqli_query($conexion, $query);

    if ($actualizacionPuntos) {
        $respuesta['status'] = 'success';
        $respuesta['puntos'] = $puntosActuales;
        // Mandamos la estructura del botón con los puntos actualizados
        $respuesta['html_puntos'] = "
            <p>Mis Puntos: <span id='contador'>".$puntosActuales."</span></p>
            <button class='boton' id='comprar'>Comprar Paquete</button>
        ";
    } else {
        $respuesta['status'] = 'error';
        $respuesta['mensaje'] = 'No se pudieron actualizar los puntos en la base de datos';
    }
} else {
    // Si no tiene saldo suficiente
    $respuesta['status'] = 'no_points';
    $respuesta['puntos'] = $resultado['puntos'];
    $respuesta['html_puntos'] = "
        <p>Mis Puntos: <span id='contador'>".$resultado['puntos']."</span></p>
        <button class='boton' id='comprar'>Comprar Paquete</button>
    ";
}

// Declaramos que la salida es estrictamente JSON y la enviamos
header('Content-Type: application/json');
echo json_encode($respuesta);
exit();
?>