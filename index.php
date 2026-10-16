<<<<<<< Updated upstream
<?php
  include('conexion.php');
  session_start();
  if (!isset($_SESSION['usuario'])){
    header('Location: login.php');
  }else {
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
    <title>Inicio</title>
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
    <button class="boton" id='contar'>+</button>
    
</div>

<script src="script/scriptAjaxPuntos.js"></script>
</body>
</html>
=======
<?php
  include('conexion.php');
  session_start();

  if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit; // corta la ejecución para que no siga cargando la página
  }

  // Una sola consulta (preparada) para puntos y admin
  $id_usuario = $_SESSION['id'];
  $stmt = mysqli_prepare($conexion, "SELECT puntos, admin FROM usuarios WHERE id = ?");
  mysqli_stmt_bind_param($stmt, "i", $id_usuario);
  mysqli_stmt_execute($stmt);
  $resultado = mysqli_stmt_get_result($stmt);
  $usuario = mysqli_fetch_assoc($resultado);
  mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style.css">
    <title>Inicio</title>
</head>
<body>

<!-- Botón de menú (solo se ve en celular) -->
<input type="checkbox" id="menu-toggle">
<label for="menu-toggle" class="hamburguesa"></label>
<label for="menu-toggle" class="menu-overlay"></label>

<nav>
    <a href="index.php">Inicio</a>
    <a href="shop.php">Tienda</a>
    <a href="inventario.php">Inventario</a>
    <?php
    /* comprueba si es admin para mostrar el panel en la navbar */
    if ($usuario && $usuario['admin'] == 1) {
        echo '<a href="adminPanel.php">Panel de administración</a>';
    }
    ?>
    <a href="logout.php">Salir</a>
</nav>
<div class="cartel"><h1>Inicio</h1></div>
<div class="caja">
    <p><span id="contador"><?php echo $usuario['puntos']; ?></span></p>
    <button class="boton" id="contar">+</button>
</div>

<script src="script/ajaxPuntos.js"></script>
</body>
</html>
>>>>>>> Stashed changes
