<?php
include('conexion.php');
session_start();

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

$id_user = $_SESSION['id'];

/* profe esta consulta la hizo chatgpt pq no sabia como hacerla */
$sql = "SELECT cartas.*, inventario.cantidad FROM inventario INNER JOIN cartas ON inventario.id_carta = cartas.id_carta WHERE inventario.id_usuario = '$id_user' ORDER BY cartas.id_carta DESC";

$resultado = mysqli_query($conexion, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario</title>
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

<div class="inventario">

<?php while ($fila = mysqli_fetch_assoc($resultado)) { ?>

    <div class="carta">

        <div class="carta-imagen">
            <img src="data:image/jpeg;base64,<?php echo base64_encode($fila['imagen']); ?>">
        </div>

        <div class="carta-info">
            <p>Rareza: <span><?php echo ucfirst($fila['rareza']); ?></span></p>
            <p>Cantidad: <span>x<?php echo $fila['cantidad']; ?></span></p>
        </div>

    </div>

<?php } ?>

</div>

</body>
</html>