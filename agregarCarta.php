<?php
  include('conexion.php');
  session_start();
  if (!isset($_SESSION['usuario'])){
    header('Location: login.php');
  }

if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] == 0) {

    $imagen = file_get_contents($_FILES['archivo']['tmp_name']);
    $imagen = mysqli_real_escape_string($conexion, $imagen); //toma la imagen desde la memoria temporal (TMP)

    $rareza = mysqli_real_escape_string($conexion, $_POST['rareza']); //toma la rareza (seguridad: impide inyecciones SQL)

    $sql = "INSERT INTO cartas (imagen, rareza)
            VALUES ('$imagen', '$rareza')"; //consulta sql

    $consulta = mysqli_query($conexion, $sql); //inserta en la bbdd

    if ($consulta) {
        echo '<script>alert("Carta agregada.");</script>';
    } else {
        echo '<script>alert("Hubo un error");</script>';
    }
    }

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Carta</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>

<nav>
    <a href="index.php">Inicio</a>
    <a href="shop.php">Tienda</a>
    <a href="biblioteca.php">Biblioteca</a>
    <a href="agregarCarta.php">Agregar carta</a>
    <a href="eliminarCarta.php">Eliminar carta</a>
    <a href="logout.php">Salir</a>
</nav>

<form method="post" enctype="multipart/form-data">

    <div class="contenedor">

        <label>Imagen de la carta:</label>
        <input type="file" name="archivo" accept="image/*" required>
        <br>

        <label>Rareza:</label>
        <select name="rareza" required>
            <option value="comun">Común</option>
            <option value="especial">Especial</option>
            <option value="raro">Raro</option>
            <option value="epico">Épico</option>
            <option value="legendario">Legendario</option>
            <option value="mitico">Mítico</option>
            <option value="divino">Divino</option>
        </select>
        <br>

        <button type="submit">Agregar carta</button>

    </div>

</form>

</body>
</html>



