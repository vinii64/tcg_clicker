<?php
include('conexion.php');
session_start();

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

$id_user = $_SESSION['id'];

// Datos del usuario (para saber si es admin y mostrar el link en el menú)
$stmt = mysqli_prepare($conexion, "SELECT admin FROM usuarios WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

/* profe esta consulta la hizo chatgpt pq no sabia como hacerla */
$sql = "SELECT cartas.*, inventario.cantidad
        FROM inventario
        INNER JOIN cartas ON inventario.id_carta = cartas.id_carta
        WHERE inventario.id_usuario = ?
        ORDER BY cartas.id_carta DESC";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario</title>
    <link rel="stylesheet" href="style/style.css">
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

<div class="cartel"><h1>Inventario</h1></div>


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