<?php
  include('conexion.php');
  session_start();
  if (!isset($_SESSION['usuario'])){
    header('Location: login.php');
    exit(); // Añadimos exit para detener la ejecución del script tras redireccionar
  } else {
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

    <!-- Links de las bibliotecas para las rarezas de las cartas -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/atropos@2.0.2/atropos.min.css" />
    <link rel="stylesheet" href="style/rarezas.css">

    <title>Tienda</title>
</head>
<body>

<nav>
  <a href="index.php">Inicio</a>
  <a href="shop.php">Tienda</a>
  <a href="inventory.php">Biblioteca</a>
  <a href="logout.php">Salir</a>
</nav>

<!-- Caja contenedora del balance de puntos y el botón de acción -->
<div class="caja">
    <p>Mis Puntos: <span id="contador"><?php echo $usuario['puntos']; ?></span></p>
    <button class="boton" id="comprar">Comprar Paquete</button>
</div>

<!-- 
  Contenedor donde JavaScript inyectará las 3 cartas de Wikipedia.
  Le añadimos la clase 'grid-tcg' que tenías originalmente en tu PHP para ordenarlas.
-->
<div id="contenedor" class="grid-tcg"></div>

<!-- Scripts necesarios al final del body -->
<script src="https://cdn.jsdelivr.net/npm/atropos@2.0.2/atropos.min.js"></script>
<script src="script/comprarPaquete.js"></script>
</body>
</html>