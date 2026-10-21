<?php
include('conexion.php');
session_start();
include('trade_lib.php');

$yo = requerirLogin();
$mensaje = null;
$okMsg = false;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $idInt  = (int)($_POST['id_intercambio'] ?? 0);

    if (!csrfValido()) {
        $res = [false, 'Sesión inválida, recargá la página.'];
    } elseif ($accion === 'aceptar') {
        $res = aceptarIntercambio($conexion, $idInt, $yo);
    } elseif ($accion === 'rechazar') {
        $res = cerrarIntercambio($conexion, $idInt, $yo, 'rechazado');
    } elseif ($accion === 'cancelar') {
        $res = cerrarIntercambio($conexion, $idInt, $yo, 'cancelado');
    } else {
        $res = [false, 'Acción no válida.'];
    }
    $_SESSION['flash'] = $res;
    header('Location: intercambios.php' . (isset($_GET['tab']) ? '?tab=' . urlencode($_GET['tab']) : ''));
    exit();
}
if (isset($_SESSION['flash'])) {
    [$okMsg, $mensaje] = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$tab = $_GET['tab'] ?? 'recibidas';
if (!in_array($tab, ['recibidas', 'enviadas', 'historial', 'usuarios'], true)) {
    $tab = 'recibidas';
}

/* ---------- datos de cada pestaña ---------- */

/** Trae intercambios con sus cartas agrupadas. $where usa ? con tipos $types. */
function cargarIntercambios(mysqli $c, string $where, string $types, array $params): array
{
    $sql = "SELECT t.*, ue.usuario AS nombre_emisor, ur.usuario AS nombre_receptor
              FROM intercambios t
              JOIN usuarios ue ON ue.id = t.id_emisor
              JOIN usuarios ur ON ur.id = t.id_receptor
             WHERE $where
             ORDER BY t.id_intercambio DESC
             LIMIT 50";
    $st = $c->prepare($sql);
    $st->bind_param($types, ...$params);
    $st->execute();
    $lista = [];
    foreach ($st->get_result() as $f) {
        $f['ofrece'] = [];
        $f['pide'] = [];
        $lista[(int)$f['id_intercambio']] = $f;
    }
    if ($lista) {
        $ids = implode(',', array_map('intval', array_keys($lista))); // ids enteros propios, no input del usuario
        $rs = $c->query("SELECT ic.id_intercambio, ic.id_carta, ic.lado, ic.cantidad, ca.rareza
                           FROM intercambio_cartas ic JOIN cartas ca ON ca.id_carta = ic.id_carta
                          WHERE ic.id_intercambio IN ($ids)");
        foreach ($rs as $it) {
            $lista[(int)$it['id_intercambio']][$it['lado']][] = $it;
        }
    }
    return $lista;
}

$lista = [];
$usuarios = [];
if ($tab === 'recibidas') {
    $lista = cargarIntercambios($conexion, "t.id_receptor = ? AND t.estado = 'pendiente'", 'i', [$yo]);
} elseif ($tab === 'enviadas') {
    $lista = cargarIntercambios($conexion, "t.id_emisor = ? AND t.estado = 'pendiente'", 'i', [$yo]);
} elseif ($tab === 'historial') {
    $lista = cargarIntercambios($conexion, "(t.id_emisor = ? OR t.id_receptor = ?) AND t.estado <> 'pendiente'", 'ii', [$yo, $yo]);
} else {
    $st = $conexion->prepare(
        'SELECT u.id, u.usuario, COALESCE(SUM(i.cantidad), 0) AS total
           FROM usuarios u LEFT JOIN inventario i ON i.id_usuario = u.id
          WHERE u.id <> ?
          GROUP BY u.id, u.usuario
          ORDER BY u.usuario'
    );
    $st->bind_param('i', $yo);
    $st->execute();
    $usuarios = $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

function pintarCartas(array $items): void
{
    if (!$items) {
        echo '<p class="vacio">(ninguna carta, fue eliminada)</p>';
        return;
    }
    echo '<div class="mini-cartas">';
    foreach ($items as $it) {
        echo '<div class="mini-carta" title="' . h(nombreRareza($it['rareza'])) . '">'
           . '<img src="carta_img.php?id=' . (int)$it['id_carta'] . '" alt="Carta" loading="lazy">'
           . '<span class="cant">x' . (int)$it['cantidad'] . '</span>'
           . '<span class="rar rar-' . h($it['rareza']) . '">' . h(nombreRareza($it['rareza'])) . '</span>'
           . '</div>';
    }
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intercambios</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>

<?php include('nav.php'); ?>

<div class="cartel"><h1>Intercambios</h1></div>

<div class="tabs">
    <a href="?tab=recibidas"  class="<?= $tab === 'recibidas' ? 'activa' : '' ?>">Recibidas</a>
    <a href="?tab=enviadas"   class="<?= $tab === 'enviadas' ? 'activa' : '' ?>">Enviadas</a>
    <a href="?tab=historial"  class="<?= $tab === 'historial' ? 'activa' : '' ?>">Historial</a>
    <a href="?tab=usuarios"   class="<?= $tab === 'usuarios' ? 'activa' : '' ?>">Nuevo intercambio</a>
</div>

<?php if ($mensaje): ?>
    <div class="aviso <?= $okMsg ? 'ok' : 'error' ?>"><?= h($mensaje) ?></div>
<?php endif; ?>

<div class="trade-wrap">

<?php if ($tab === 'usuarios'): ?>

    <p class="ayuda">Elegí con quién querés intercambiar cartas.</p>
    <?php if (!$usuarios): ?>
        <p class="vacio">Todavía no hay otros usuarios.</p>
    <?php endif; ?>
    <div class="usuarios-lista">
        <?php foreach ($usuarios as $u): ?>
            <a class="usuario-item" href="proponer.php?con=<?= (int)$u['id'] ?>">
                <strong><?= h($u['usuario']) ?></strong>
                <span><?= (int)$u['total'] ?> cartas</span>
            </a>
        <?php endforeach; ?>
    </div>

<?php else: ?>

    <?php if (!$lista): ?>
        <p class="vacio">
            <?= $tab === 'recibidas' ? 'No tenés propuestas pendientes.' : ($tab === 'enviadas' ? 'No enviaste propuestas pendientes.' : 'Todavía no hay intercambios en tu historial.') ?>
        </p>
    <?php endif; ?>

    <?php foreach ($lista as $t):
        $soyEmisor = (int)$t['id_emisor'] === $yo;
        $otro = $soyEmisor ? $t['nombre_receptor'] : $t['nombre_emisor'];
   
        $das      = $soyEmisor ? $t['ofrece'] : $t['pide'];
        $recibis  = $soyEmisor ? $t['pide']   : $t['ofrece'];
    ?>
        <div class="trade-card">
            <div class="trade-head">
                <span>#<?= (int)$t['id_intercambio'] ?> · con <strong><?= h($otro) ?></strong></span>
                <span class="estado estado-<?= h($t['estado']) ?>"><?= h(ucfirst($t['estado'])) ?></span>
            </div>
            <div class="trade-body">
                <div>
                    <h3>Vos das</h3>
                    <?php pintarCartas($das); ?>
                </div>
                <div class="flecha">⇄</div>
                <div>
                    <h3>Vos recibís</h3>
                    <?php pintarCartas($recibis); ?>
                </div>
            </div>
            <div class="trade-fecha">
                <?= h($t['fecha_creacion']) ?>
                <?php if ($t['fecha_resolucion']) echo ' → ' . h($t['fecha_resolucion']); ?>
            </div>

            <?php if ($t['estado'] === 'pendiente'): ?>
                <div class="trade-acciones">
                    <?php if (!$soyEmisor): ?>
                        <form method="post" action="intercambios.php?tab=<?= h($tab) ?>">
                            <?= csrfCampo() ?>
                            <input type="hidden" name="id_intercambio" value="<?= (int)$t['id_intercambio'] ?>">
                            <button name="accion" value="aceptar" class="btn-ok">Aceptar</button>
                            <button name="accion" value="rechazar" class="btn-no">Rechazar</button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="intercambios.php?tab=<?= h($tab) ?>">
                            <?= csrfCampo() ?>
                            <input type="hidden" name="id_intercambio" value="<?= (int)$t['id_intercambio'] ?>">
                            <button name="accion" value="cancelar" class="btn-no">Cancelar propuesta</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

</div>
</body>
</html>
