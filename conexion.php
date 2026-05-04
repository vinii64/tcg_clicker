<?php
    $conexion = new mysqli("localhost","root","","tcgdb");

    if ($conexion->connect_error){
        die("Error al conectarse".$conexion->connect_error);
    }
?>