<?php
    include('conexion.php');
    if (isset($_POST['email'])){
        $email = $_POST['email'];
        $contrasenia = $_POST['contrasenia'];

        $sql = "SELECT * FROM usuarios WHERE email = '".$email."'";
        
        $resultado = mysqli_query($conexion,$sql);
        $filas = mysqli_num_rows($resultado);

        if($filas == 0){
            echo "<script>alert('Correo o contraseña incorrecta')</script>";
        }
        else {

            $datos = mysqli_fetch_assoc($resultado);
            $contraDb = $datos['contrasenia'];
            $contraForm = md5($contrasenia);
         
            if($contraForm == $contraDb){
                session_start();
                $_SESSION['usuario'] = $datos['usuario'];
                $_SESSION['id'] = $datos['id'];
                header("Location: index.php");
            }
            else {
                echo "<script>alert('Correo o contraseña incorrecta')</script>";
            }
        }
    }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/styleLogin.css">
    <title>Document</title>
</head>
<body>
    <div class="login-container">
        <h1>Inicia sesión</h1>
        <form action="" method="post">
            <input type="text" name="email" placeholder="E-mail"></input> <br></br>
            <input type="password" name="contrasenia" placeholder="Contraseña"></input> <br></br>
            <button type="submit">Aceptar</button><br><br><br>   
        </form>

        <a class="reg" href="register.php">Registrarse</a>

    </div>


    
</body>
</html>