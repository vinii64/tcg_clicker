<?php
include('conexion.php');

session_start();

if (!isset($_SESSION['usuario'])) {

    header('Location: login.php');
    exit();

} else {

    $id_usuario = $_SESSION['id'];

    $query = "SELECT puntos FROM usuarios WHERE id = '$id_usuario'";

    $resultado = mysqli_query($conexion, $query);

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

    <h2>Puntos: <span id="contador"><?php echo $usuario['puntos']; ?></span></h2>

    <button class="boton" id="comprar">
        Comprar Paquete
    </button>

  <div id="booster-card" style="border: 2px solid #333; border-radius: 15px; padding: 20px; width: 300px; text-align: center; font-family: sans-serif;">
      <h2 id="card-title" style="margin-top: 0;">Cargando...</h2>
      <div style="height: 200px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #f0f0f0; border-radius: 8px;">
          <img id="card-image" src="" alt="" style="max-width: 100%; max-height: 100%; display: none;">
      </div>
      <p id="card-desc" style="font-size: 0.9em; color: #555; line-height: 1.4; height: 100px; overflow-y: auto; margin-top: 15px;"></p>
  </div>


</div>

<script src="script/comprarPaquete.js"></script>

</body>
</html>