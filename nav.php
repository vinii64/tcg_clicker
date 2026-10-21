<?php
/* esto pone en el navbar el coso de intercambis en todosl os phps */
$__id = (int)$_SESSION['id'];
$__pend = pendientesRecibidos($conexion, $__id);
?>
<nav>
    <a href="index.php">Inicio</a>
    <a href="shop.php">Tienda</a>
    <a href="inventario.php">Inventario</a>
    <a href="intercambios.php">Intercambios<?php if ($__pend > 0) echo ' <span class="badge">' . $__pend . '</span>'; ?></a>
    <?php if (esAdmin($conexion, $__id)) echo '<a href="adminPanel.php">Panel de adminstración</a>'; ?>
    <a href="logout.php">Salir</a>
</nav>
