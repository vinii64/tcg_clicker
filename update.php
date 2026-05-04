<?php
    include('conexion.php');
    session_start();
    $id_usuario = $_SESSION['id'];

    //actualizacion de puntos
    $update = "UPDATE usuarios SET puntos = puntos + 1 WHERE id = '".$id_usuario."'";
    mysqli_query($conexion, $update);

    //seleccion de puntos
    $query = "SELECT puntos FROM usuarios WHERE id = '".$id_usuario."'";
    $resultado = mysqli_query($conexion,$query);
    $usuario = mysqli_fetch_assoc($resultado);

    //respuesta
    echo $usuario['puntos'];

    //cierre de conexion
    mysqli_close($conexion);
?>