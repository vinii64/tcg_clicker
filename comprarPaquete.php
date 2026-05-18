<?php

include('conexion.php');

session_start();

if (!isset($_SESSION['usuario'])) {

    exit();
}

$id_usuario = $_SESSION['id'];


// Obtener puntos actuales
$query = "SELECT puntos FROM usuarios WHERE id = '$id_usuario'";

$resultado = mysqli_query($conexion, $query);

$usuario = mysqli_fetch_assoc($resultado);

$puntos = $usuario['puntos'];


// Verificar si tiene puntos
if($puntos <= 0){

    echo $puntos;

    exit();
}


// Restar 1 punto
$query2 = "UPDATE usuarios 
           SET puntos = puntos - 1 
           WHERE id = '$id_usuario'";

mysqli_query($conexion, $query2);


// Obtener nuevos puntos
$query3 = "SELECT puntos FROM usuarios WHERE id = '$id_usuario'";

$resultado2 = mysqli_query($conexion, $query3);

$usuario2 = mysqli_fetch_assoc($resultado2);


// Devolver nuevos puntos
echo $usuario2['puntos'];

?>