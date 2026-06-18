<?php
include('conexion.php');
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

$id = $_SESSION['id'];

$queryAdmin = "SELECT admin FROM usuarios WHERE id = '$id'";
$resultadoAdmin = mysqli_query($conexion, $queryAdmin);
$admin = mysqli_fetch_assoc($resultadoAdmin);

if (!$admin || $admin['admin'] != 1) {
    header('Location: index.php');
    exit();
}


/* AGREGAR CARTA */
if (
    isset($_POST['accion']) &&
    $_POST['accion'] == 'agregar' &&
    isset($_FILES['archivo']) &&
    $_FILES['archivo']['error'] == 0
) {

    $imagen = file_get_contents($_FILES['archivo']['tmp_name']);
    $imagen = mysqli_real_escape_string($conexion, $imagen);

    $rareza = mysqli_real_escape_string($conexion, $_POST['rareza']);

    $sql = "INSERT INTO cartas (imagen, rareza)
            VALUES ('$imagen', '$rareza')";

    if (mysqli_query($conexion, $sql)) {
        echo "<script>alert('Carta agregada correctamente');</script>";
    } else {
        echo "<script>alert('Error al agregar la carta');</script>";
    }
}

/* ELIMINAR CARTA */

if (
    isset($_POST['accion']) &&
    $_POST['accion'] == 'eliminar'
) {
    
    $id_carta = (int)$_POST['id_carta'];
    $consultaEliminarCartaInventario = "DELETE FROM inventario WHERE id_carta = $id_carta";

    mysqli_query($conexion,$consultaEliminarCartaInventario);

    $sql = "DELETE FROM cartas WHERE id_carta = $id_carta";

    if (mysqli_query($conexion, $sql)) {
        echo "<script>alert('Carta eliminada correctamente');</script>";
    } else {
        echo "<script>alert('Error al eliminar la carta');</script>";
    }
}

/* ACTUALIZAR RAREZA */
if (
    isset($_POST['accion']) &&
    $_POST['accion'] == 'actualizar'
) {

    $id_carta = (int)$_POST['id_carta'];
    $rareza = mysqli_real_escape_string($conexion, $_POST['rareza']);

    $sql = "UPDATE cartas
            SET rareza = '$rareza'
            WHERE id_carta = $id_carta";

    if (mysqli_query($conexion, $sql)) {
        echo "<script>alert('Carta actualizada correctamente');</script>";
    } else {
        echo "<script>alert('Error al actualizar la carta');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de admin.</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>

<nav>
    <a href="index.php">Inicio</a>
    <a href="shop.php">Tienda</a>
    <a href="inventario.php">Inventario</a>
    <?php
    /*comprueba si es admin para mostrar biblitoeca en la navbar*/
    $id = $_SESSION['id'];

    $queryAdmin = "SELECT admin FROM usuarios WHERE id = '$id'";
    $resultadoAdmin = mysqli_query($conexion, $queryAdmin);
    $admin = mysqli_fetch_assoc($resultadoAdmin);

    if ($admin && $admin['admin'] == 1) {
        echo '<a href="adminPanel.php">Panel de adminstración</a>';
    }
    ?>

    <a href="logout.php">Salir</a>
</nav>
<div class="cartel"><h1>Panel de administración.</h1></div>

<div class="agregar">
    
    <form method="post" enctype="multipart/form-data">

        <input type="hidden" name="accion" value="agregar">

        <div class="contenedor">
            <h1>Agregar carta</h1>
            <label>Imagen de la carta:</label>
            <input type="file" name="archivo" accept="image/*" required>

            <br>

            <label>Rareza:</label>
            <select name="rareza" required>
                <option value="comun">Comun</option>
                <option value="especial">Especial</option>
                <option value="raro">Rara</option>
                <option value="epico">Epica</option>
                <option value="legendario">Legendaria</option>
                <option value="mitico">Mitica</option>
                <option value="divino">Divina</option>
            </select>

            <br>

            <button type="submit">Agregar</button>
        </div>

    </form>
</div>

<div class="inventario">

<?php
$sql = "SELECT * FROM cartas ORDER BY id_carta DESC";
$resultado = mysqli_query($conexion, $sql);

while ($fila = mysqli_fetch_assoc($resultado)) {
?>

    <div class="carta">

        <img src="data:image/jpeg;base64,<?php echo base64_encode($fila['imagen']); ?>" alt="Carta">

        <!-- ELIMINAR -->
        <form method="post">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id_carta" value="<?php echo $fila['id_carta']; ?>">

            <button type="submit">Eliminar carta</button>
        </form>

        <!-- ACTUALIZAR RAREZA -->
        <form method="post">

            <input type="hidden" name="accion" value="actualizar">
            <input type="hidden" name="id_carta" value="<?php echo $fila['id_carta']; ?>">

            <select name="rareza">

                <option value="comun" <?php if ($fila['rareza'] == 'comun') echo 'selected'; ?>>
                    Comun
                </option>

                <option value="especial" <?php if ($fila['rareza'] == 'especial') echo 'selected'; ?>>
                    Especial
                </option>

                <option value="raro" <?php if ($fila['rareza'] == 'raro') echo 'selected'; ?>>
                    Rara
                </option>

                <option value="epico" <?php if ($fila['rareza'] == 'epico') echo 'selected'; ?>>
                    Epica
                </option>

                <option value="legendario" <?php if ($fila['rareza'] == 'legendario') echo 'selected'; ?>>
                    Legendaria
                </option>

                <option value="mitico" <?php if ($fila['rareza'] == 'mitico') echo 'selected'; ?>>
                    Mitica
                </option>

                <option value="divino" <?php if ($fila['rareza'] == 'divino') echo 'selected'; ?>>
                    Divina
                </option>

            </select>

            <button type="submit">Actualizar carta</button>

        </form>

    </div>

<?php
}
?>

</div>

</body>
</html>