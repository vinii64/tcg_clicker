<?php
  include('conexion.php');
  session_start();
  if (!isset($_SESSION['usuario'])){
    header('Location: login.php');
  }
  else
    {
    $id_usuario = $_SESSION['id'];
    $query = "SELECT puntos FROM usuarios WHERE id = '".$id_usuario."'";
    $resultado = mysqli_query($conexion,$query);
    $usuario = mysqli_fetch_assoc($resultado);
  }

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style.css">
    <title>Tienda</title>
</head>
<body>

<nav>
  <a href="index.php">Inicio</a>
  <a href="shop.php">Tienda</a>
  <a href="inventory.php">Biblioteca</a>
  <a href="logout.php">Salir</a>
</nav>

<div class="caja">
  <p><span id="contador"><?php echo $usuario['puntos']; ?></span></p>
    <button class="boton" id='comprar'>Comprar Paquete</button>
    <div id="contenedor"></div>
</div>

<script src="script/comprarPaquete.js"></script>
</body>
</html>
