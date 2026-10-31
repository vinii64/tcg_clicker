<?php
include('conexion.php');
session_start();

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

$id_user = $_SESSION['id'];

/* profe esta consulta la hizo chatgpt pq no sabia como hacerla */
$sql = "SELECT cartas.*, inventario.cantidad, inventario.edicion FROM inventario INNER JOIN cartas ON inventario.id_carta = cartas.id_carta WHERE inventario.id_usuario = '$id_user' ORDER BY cartas.id_carta DESC, inventario.edicion ASC";

$resultado = mysqli_query($conexion, $sql);

/* ediciones: clave guardada en la base => texto que se muestra */
$ediciones = [
    'normal'      => 'Normal',
    'brillante'   => 'Brillante',
    'holografica' => 'Holográfica',
    'policroma'   => 'Policroma',
    'negativa'    => 'Negativa'
];

/* guardamos las cartas y contamos cuántas tiene de cada edición (para los filtros) */
$cartasInv = [];
$conteo = array_fill_keys(array_keys($ediciones), 0);
while ($fila = mysqli_fetch_assoc($resultado)) {
    $cartasInv[] = $fila;
    if (isset($conteo[$fila['edicion']])) {
        $conteo[$fila['edicion']] += (int)$fila['cantidad'];
    }
}
$totalCartas = array_sum($conteo);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario</title>
    <link rel="stylesheet" href="style/style.css?v=3">
</head>
<body>

<nav>
    <a href="index.php">Inicio</a>
    <a href="shop.php">Tienda</a>
    <a href="inventario.php">Inventario</a>
    <a href="intercambios.php">Intercambios</a>
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

<div class="cartel"><h1>Inventario</h1></div>

<?php if ($cartasInv) { ?>
<!-- filtros por edición: el número es la cantidad de cartas de esa edición -->
<div class="ed-filtros" role="group" aria-label="Filtrar por edición">
    <button type="button" class="ed-chip activo" data-ed="todas">Todas <b><?php echo $totalCartas; ?></b></button>
    <?php foreach ($ediciones as $clave => $nombre) { ?>
        <button type="button" class="ed-chip" data-ed="<?php echo $clave; ?>" <?php if ($conteo[$clave] == 0) echo 'disabled'; ?>>
            <i class="ed-punto ed-pill-<?php echo $clave; ?>"></i><?php echo $nombre; ?> <b><?php echo $conteo[$clave]; ?></b>
        </button>
    <?php } ?>
</div>
<?php } else { ?>
    <p class="vacio">Todavía no tenés cartas. ¡Pasá por la tienda a abrir un paquete!</p>
<?php } ?>

<p class="vacio" id="ed-vacio" hidden>No tenés cartas de esta edición.</p>

<div class="inventario">

<?php foreach ($cartasInv as $fila) {
    $ed = isset($ediciones[$fila['edicion']]) ? $fila['edicion'] : 'normal';
?>

    <div class="carta ed-<?php echo $ed; ?>" data-ed="<?php echo $ed; ?>">

        <div class="carta-imagen ed-fx">
            <img src="data:image/jpeg;base64,<?php echo base64_encode($fila['imagen']); ?>">
        </div>

        <div class="carta-info">
            <p>Rareza: <span><?php echo ucfirst($fila['rareza']); ?></span></p>
            <p>Edición: <span class="ed-pill ed-pill-<?php echo $ed; ?>"><?php echo $ediciones[$ed]; ?></span></p>
            <p>Cantidad: <span>x<?php echo $fila['cantidad']; ?></span></p>
        </div>

    </div>

<?php } ?>

</div>

<script src="script/menu.js"></script>
<script src="script/inventarioFiltro.js"></script>
</body>
</html>
