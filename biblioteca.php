<?php
  include('conexion.php');
  session_start();
  if (!isset($_SESSION['usuario'])){
    header('Location: login.php');
  }

  
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style.css">
    <title>Biblioteca</title>
</head>


<nav>
  <a href="index.php">Inicio</a>
  <a href="shop.php">Tienda</a>
  <a href="biblioteca.php">Biblioteca</a>
  <a href="logout.php">Salir</a>
  <a href="agregarCarta.php">Agregar carta</a>
  <a href="eliminarCarta.php">Eliminar carta</a>
</nav>

<div class="inventario">
  <?php
  $sql = "SELECT * FROM cartas";
  $resultado = mysqli_query($conexion, $sql);

  while ($fila = mysqli_fetch_assoc($resultado)) {
  ?>
      <div class="carta">
        <img src="data:image/jpeg;base64,<?php echo base64_encode($fila['imagen']) ?> ">
      </div>
  <?php
  }
  ?>
</div>
</body>
</html>









