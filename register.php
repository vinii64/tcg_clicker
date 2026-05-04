<?php

    include('conexion.php');
    if (isset($_POST['usuario'])){
        $email = $_POST['email'];
        $usuario = $_POST['usuario'];
        $contrasenia = $_POST['contrasenia'];

        $sql = "SELECT * FROM usuarios WHERE email = '".$email."'";

        $resultado = mysqli_query($conexion,$sql);
        $filas = mysqli_num_rows($resultado);
        

        if($filas == 0){
            $contraEncriptada = md5($contrasenia);
            $consulta="INSERT INTO usuarios (email, usuario, contrasenia) VALUES
            ('".$_POST['email']."','".$_POST['usuario']."','".$contraEncriptada."')";
            $resultado = mysqli_query($conexion,$consulta);
            header("Location: login.php");
        }
        else{
            echo "<script>alert('El correo ya esta en uso')</script>";
        }
    } 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style/styleLogin.css">
</head>
<body>
    <div class="login-container">
        <h1>Registra tu cuenta</h1>
        <form action="" method="post">
            <input type="text" name="email" placeholder="E-mail"></input> <br></br>
            <input type="text" name="usuario" placeholder="Usuario"></input> <br></br>
            <input type="password" name="contrasenia" placeholder="Contraseña"></input> <br></br>
            <button type="submit">Aceptar</button>
        </form>
    </div>


</body>
</html>

