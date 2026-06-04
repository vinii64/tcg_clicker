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
        echo "carta ingresada correctamente";
        $resultado = mysqli_query($conexion, "SELECT imagen FROM cartas WHERE id = 46");
        $fila = mysqli_fetch_assoc($resultado);

       //linea para mostrar la carta con el BLOB echo "<img src='data:image/jpeg;base64,".base64_encode($fila['imagen'])."'>";
    } else {
        echo "hubo un error al subir la imagen";
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
  <a href="agregarCarta.php">Agregar carta</a>
  <a href="eliminarCarta.php">Eliminar carta</a>
    <a href="logout.php">Salir</a>
</nav>

  <form method="post" enctype="multipart/form-data">
    
    <div class="contenedor">
      <label for="myfile">Imagen de la carta:</label>
      <input type="file" name="archivo"><br>

      <label for="rareza">Rareza:</label>
      <select name="rareza" id="rareza">
          <option value="comun">Común</option>
          <option value="especial">Especial</option>
          <option value="raro">Raro</option>
          <option value="epico">Épico</option>
          <option value="legendario">Legendario</option>
          <option value="mitico">Mítico</option>
          <option value="divino">Divino</option>
      </select><br>
      <button type="submit">Agregar carta</button>
    </div>
  </form>
    
</body>
</html>