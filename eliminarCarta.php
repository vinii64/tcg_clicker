<?php
    include('conexion.php');
    session_start();
    if (!isset($_SESSION['usuario'])){
        header('Location: login.php');
    }



    if (isset($_POST['id_carta'])) {
        $id_carta = $_POST['id_carta'];
        $sql = "DELETE FROM cartas WHERE id_carta = '$id_carta'";

        $consulta = mysqli_query($conexion, $sql);
        if ($consulta) {
            echo "<script>alert('Carta eliminada correctamente');</script>";
        } else {
            echo "<script>alert('Error: " . mysqli_error($conexion) . "');</script>";
        }
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>

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

    <form method="post" action="">
      <div class="carta">
        <img src="data:image/jpeg;base64,<?php echo base64_encode($fila['imagen']) ?> ">
        <input hidden type="number" name="id_carta" value="<?php echo $fila['id_carta']; ?>">
        <button type="submit">eliminar carta</button>
      </div>
    </form>

  <?php
  }
  ?>
</div>
    
</body>
</html>