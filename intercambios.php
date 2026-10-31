<?php
/*
 * SISTEMA DE INTERCAMBIOS EN UN SOLO ARCHIVO
 *
 * Rutas (todas dentro de este mismo archivo):
 *   intercambios.php?tab=recibidas|enviadas|historial|usuarios  -> listados
 *   intercambios.php?con=ID                                     -> armar propuesta con el usuario ID
 *   intercambios.php?img=ID                                     -> devuelve la imagen de la carta ID
 */

include('conexion.php');
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conexion->set_charset('utf8mb4');

/* =====================================================================
 * 1. FUNCIONES AUXILIARES (antes: trade_lib.php)
 * ===================================================================== */

const MAX_CARTAS_POR_LADO = 10;

/* Ediciones posibles (clave guardada en la base => texto que se muestra) */
const EDICIONES = [
    'normal'      => 'Normal',
    'brillante'   => 'Brillante',
    'holografica' => 'Holográfica',
    'policroma'   => 'Policroma',
    'negativa'    => 'Negativa',
];

function nombreEdicion(string $e): string
{
    return EDICIONES[$e] ?? ucfirst($e);
}

/* Una "pila" de cartas es carta + edición: se identifica con "ID:edicion" (ej. "12:holografica") */
function claveCarta(int $idCarta, string $edicion): string
{
    return $idCarta . ':' . $edicion;
}

function partirClave(string $clave): array
{
    $p = explode(':', $clave, 2);
    return [(int)$p[0], $p[1] ?? 'normal'];
}

function requerirLogin(): int
{
    if (!isset($_SESSION['id'])) {
        header('Location: login.php');
        exit();
    }
    return (int)$_SESSION['id'];
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfCampo(): string
{
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}

function csrfValido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function esAdmin(mysqli $c, int $id): bool
{
    $st = $c->prepare('SELECT admin FROM usuarios WHERE id = ?');
    $st->bind_param('i', $id);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    return $r && (int)$r['admin'] === 1;
}

/** Inventario de un usuario: [id_carta => ['rareza'=>..., 'cantidad'=>...]] */
function inventarioDe(mysqli $c, int $idUsuario): array
{
    $st = $c->prepare(
        'SELECT c.id_carta, c.rareza, i.edicion, i.cantidad
           FROM inventario i
           JOIN cartas c ON c.id_carta = i.id_carta
          WHERE i.id_usuario = ? AND i.cantidad > 0
          ORDER BY c.id_carta DESC, i.edicion'
    );
    $st->bind_param('i', $idUsuario);
    $st->execute();
    $out = [];
    foreach ($st->get_result() as $f) {
        $out[claveCarta((int)$f['id_carta'], $f['edicion'])] = [
            'id_carta' => (int)$f['id_carta'],
            'edicion'  => $f['edicion'],
            'rareza'   => $f['rareza'],
            'cantidad' => (int)$f['cantidad'],
        ];
    }
    return $out;
}

function usuarioExiste(mysqli $c, int $id): ?string
{
    $st = $c->prepare('SELECT usuario FROM usuarios WHERE id = ?');
    $st->bind_param('i', $id);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    return $r ? $r['usuario'] : null;
}

function nombreRareza(string $r): string
{
    $map = [
        'comun' => 'Común', 'especial' => 'Especial', 'raro' => 'Rara', 'epico' => 'Épica',
        'legendario' => 'Legendaria', 'mitico' => 'Mítica', 'divino' => 'Divina',
    ];
    return $map[$r] ?? ucfirst($r);
}

function pendientesRecibidos(mysqli $c, int $idUsuario): int
{
    $st = $c->prepare("SELECT COUNT(*) FROM intercambios WHERE id_receptor = ? AND estado = 'pendiente'");
    $st->bind_param('i', $idUsuario);
    $st->execute();
    return (int)$st->get_result()->fetch_row()[0];
}

/** Normaliza el input ofrece[id]=cant a [id => cant] con enteros positivos. */
function leerSeleccion($raw): array
{
    $out = [];
    if (is_array($raw)) {
        foreach ($raw as $clave => $q) {
            $partes = explode(':', (string)$clave, 2);
            $id = (int)$partes[0];
            $ed = $partes[1] ?? '';
            $q = (int)$q;
            if ($id > 0 && $q > 0 && isset(EDICIONES[$ed])) {
                $out[claveCarta($id, $ed)] = $q;
            }
        }
    }
    return $out;
}

/* =====================================================================
 * 2. ACCIONES DEL INTERCAMBIO (antes: trade_lib.php)
 * ===================================================================== */

/**
 * Crea una propuesta. $ofrece y $pide son [id_carta => cantidad].
 * Devuelve [ok(bool), mensaje(string)].
 */
function proponerIntercambio(mysqli $c, int $emisor, int $receptor, array $ofrece, array $pide): array
{
    if ($emisor === $receptor) {
        return [false, 'No podés intercambiar con vos mismo.'];
    }
    if (usuarioExiste($c, $receptor) === null) {
        return [false, 'El usuario no existe.'];
    }

    $ofrece = array_filter($ofrece, fn($q) => $q > 0);
    $pide   = array_filter($pide,   fn($q) => $q > 0);

    if (!$ofrece || !$pide) {
        return [false, 'Tenés que ofrecer al menos una carta y pedir al menos una carta.'];
    }
    if (count($ofrece) > MAX_CARTAS_POR_LADO || count($pide) > MAX_CARTAS_POR_LADO) {
        return [false, 'Máximo ' . MAX_CARTAS_POR_LADO . ' cartas distintas por lado.'];
    }

    // Validación contra los inventarios actuales (se vuelve a validar al aceptar)
    $invE = inventarioDe($c, $emisor);
    $invR = inventarioDe($c, $receptor);
    foreach ($ofrece as $id => $q) {
        if (!isset($invE[$id]) || $invE[$id]['cantidad'] < $q) {
            return [false, 'No tenés suficientes copias de una de las cartas que ofrecés.'];
        }
    }
    foreach ($pide as $id => $q) {
        if (!isset($invR[$id]) || $invR[$id]['cantidad'] < $q) {
            return [false, 'El otro usuario no tiene alguna de las cartas que pedís.'];
        }
    }

    $c->begin_transaction();
    try {
        $st = $c->prepare('INSERT INTO intercambios (id_emisor, id_receptor) VALUES (?, ?)');
        $st->bind_param('ii', $emisor, $receptor);
        $st->execute();
        $idInt = $c->insert_id;

        $st = $c->prepare('INSERT INTO intercambio_cartas (id_intercambio, id_carta, edicion, lado, cantidad) VALUES (?, ?, ?, ?, ?)');
        foreach (['ofrece' => $ofrece, 'pide' => $pide] as $lado => $lista) {
            foreach ($lista as $clave => $cant) {
                [$idCarta, $edicion] = partirClave((string)$clave);
                $st->bind_param('iissi', $idInt, $idCarta, $edicion, $lado, $cant);
                $st->execute();
            }
        }
        $c->commit();
    } catch (Throwable $e) {
        $c->rollback();
        return [false, 'No se pudo crear la propuesta.'];
    }
    return [true, 'Propuesta enviada.'];
}

/** Rechaza (receptor) o cancela (emisor) una propuesta pendiente. */
function cerrarIntercambio(mysqli $c, int $idInt, int $usuario, string $nuevoEstado): array
{
    // rechazado -> sólo receptor; cancelado -> sólo emisor
    $campo = $nuevoEstado === 'rechazado' ? 'id_receptor' : 'id_emisor';
    $sql = "UPDATE intercambios SET estado = ?, fecha_resolucion = NOW()
             WHERE id_intercambio = ? AND $campo = ? AND estado = 'pendiente'";
    $st = $c->prepare($sql);
    $st->bind_param('sii', $nuevoEstado, $idInt, $usuario);
    $st->execute();
    if ($st->affected_rows !== 1) {
        return [false, 'La propuesta no existe, ya fue resuelta o no podés modificarla.'];
    }
    return [true, $nuevoEstado === 'rechazado' ? 'Propuesta rechazada.' : 'Propuesta cancelada.'];
}

/** Mueve $cant copias de una carta (con su edición) de un usuario a otro, dentro de una transacción abierta. */
function transferirCarta(mysqli $c, int $de, int $a, int $idCarta, string $edicion, int $cant): void
{
    $st = $c->prepare('UPDATE inventario SET cantidad = cantidad - ? WHERE id_usuario = ? AND id_carta = ? AND edicion = ? AND cantidad >= ?');
    $st->bind_param('iiisi', $cant, $de, $idCarta, $edicion, $cant);
    $st->execute();
    if ($st->affected_rows !== 1) {
        throw new RuntimeException('Inventario insuficiente');
    }
    $st->close();

    $st = $c->prepare('DELETE FROM inventario WHERE id_usuario = ? AND id_carta = ? AND edicion = ? AND cantidad <= 0');
    $st->bind_param('iis', $de, $idCarta, $edicion);
    $st->execute();
    $st->close();

    $st = $c->prepare('UPDATE inventario SET cantidad = cantidad + ? WHERE id_usuario = ? AND id_carta = ? AND edicion = ?');
    $st->bind_param('iiis', $cant, $a, $idCarta, $edicion);
    $st->execute();
    $existia = $st->affected_rows > 0;
    $st->close();

    if (!$existia) {
        $st = $c->prepare('INSERT INTO inventario (id_usuario, id_carta, edicion, cantidad) VALUES (?, ?, ?, ?)');
        $st->bind_param('iisi', $a, $idCarta, $edicion, $cant);
        $st->execute();
        $st->close();
    }
}

/** El receptor acepta: todo el canje ocurre en una única transacción (o no ocurre nada). */
function aceptarIntercambio(mysqli $c, int $idInt, int $usuario): array
{
    $c->begin_transaction();
    try {
        // Bloquea la fila para evitar doble aceptación simultánea
        $st = $c->prepare('SELECT id_emisor, id_receptor, estado FROM intercambios WHERE id_intercambio = ? FOR UPDATE');
        $st->bind_param('i', $idInt);
        $st->execute();
        $t = $st->get_result()->fetch_assoc();

        if (!$t || (int)$t['id_receptor'] !== $usuario || $t['estado'] !== 'pendiente') {
            $c->rollback();
            return [false, 'La propuesta no existe, ya fue resuelta o no podés aceptarla.'];
        }
        $emisor = (int)$t['id_emisor'];

        $st = $c->prepare('SELECT id_carta, edicion, lado, cantidad FROM intercambio_cartas WHERE id_intercambio = ?');
        $st->bind_param('i', $idInt);
        $st->execute();
        $items = $st->get_result()->fetch_all(MYSQLI_ASSOC);

        $hayOfrece = $hayPide = false;
        foreach ($items as $it) {
            $it['lado'] === 'ofrece' ? $hayOfrece = true : $hayPide = true;
        }
        if (!$hayOfrece || !$hayPide) {
            // por ejemplo, el admin borró una carta que formaba parte de la propuesta
            $c->rollback();
            return [false, 'La propuesta ya no es válida (alguna carta fue eliminada).'];
        }

        foreach ($items as $it) {
            $idCarta = (int)$it['id_carta'];
            $ed      = (string)$it['edicion'];
            $cant    = (int)$it['cantidad'];
            if ($it['lado'] === 'ofrece') {
                transferirCarta($c, $emisor, $usuario, $idCarta, $ed, $cant);
            } else {
                transferirCarta($c, $usuario, $emisor, $idCarta, $ed, $cant);
            }
        }

        $st = $c->prepare("UPDATE intercambios SET estado = 'aceptado', fecha_resolucion = NOW() WHERE id_intercambio = ?");
        $st->bind_param('i', $idInt);
        $st->execute();

        $c->commit();
        return [true, '¡Intercambio realizado!'];
    } catch (RuntimeException $e) {
        $c->rollback();
        return [false, 'Alguno de los dos ya no tiene las cartas necesarias. El intercambio no se realizó.'];
    } catch (Throwable $e) {
        $c->rollback();
        return [false, 'Error al procesar el intercambio.'];
    }
}

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
        $rs = $c->query("SELECT ic.id_intercambio, ic.id_carta, ic.edicion, ic.lado, ic.cantidad, ca.rareza
                           FROM intercambio_cartas ic JOIN cartas ca ON ca.id_carta = ic.id_carta
                          WHERE ic.id_intercambio IN ($ids)");
        foreach ($rs as $it) {
            $lista[(int)$it['id_intercambio']][$it['lado']][] = $it;
        }
    }
    return $lista;
}

/* =====================================================================
 * 3. FUNCIONES DE DIBUJO
 * ===================================================================== */

function pintarCartas(array $items): void
{
    if (!$items) {
        echo '<p class="vacio">(ninguna carta, fue eliminada)</p>';
        return;
    }
    echo '<div class="mini-cartas">';
    foreach ($items as $it) {
        $ed = isset(EDICIONES[$it['edicion']]) ? $it['edicion'] : 'normal';
        echo '<div class="mini-carta ed-' . h($ed) . '" title="' . h(nombreRareza($it['rareza'])) . ' · ' . h(nombreEdicion($ed)) . '">'
           . '<div class="ed-fx"><img src="intercambios.php?img=' . (int)$it['id_carta'] . '" alt="Carta" loading="lazy"></div>'
           . '<span class="cant">x' . (int)$it['cantidad'] . '</span>'
           . '<span class="rar rar-' . h($it['rareza']) . '">' . h(nombreRareza($it['rareza'])) . '</span>'
           . '<span class="ed-mini"><i class="ed-punto ed-pill-' . h($ed) . '"></i>' . h(nombreEdicion($ed)) . '</span>'
           . '</div>';
    }
    echo '</div>';
}

function pintarSeleccion(array $inv, string $campo): void
{
    if (!$inv) {
        echo '<p class="vacio">No hay cartas disponibles.</p>';
        return;
    }
    echo '<div class="inventario sel-grid">';
    foreach ($inv as $clave => $d) {
        $max = (int)$d['cantidad'];
        $ed  = isset(EDICIONES[$d['edicion']]) ? $d['edicion'] : 'normal';
        echo '<label class="carta sel-carta ed-' . h($ed) . '">'
           . '<div class="ed-fx"><img src="intercambios.php?img=' . (int)$d['id_carta'] . '" alt="Carta" loading="lazy"></div>'
           . '<div class="carta-info"><p>Rareza: <span>' . h(nombreRareza($d['rareza'])) . '</span></p>'
           . '<p>Edición: <span class="ed-pill ed-pill-' . h($ed) . '">' . h(nombreEdicion($ed)) . '</span></p>'
           . '<p>Disponibles: <span>x' . $max . '</span></p></div>'
           . '<div class="sel-cant"><span>Cantidad</span>'
           . '<input type="number" name="' . h($campo) . '[' . h($clave) . ']" min="0" max="' . $max . '" value="0"></div>'
           . '</label>';
    }
    echo '</div>';
}

/** Navbar (antes: nav.php) */
function pintarNav(mysqli $c, int $idUsuario): void
{
    $pend = pendientesRecibidos($c, $idUsuario);
    ?>
<nav>
    <a href="index.php">Inicio</a>
    <a href="shop.php">Tienda</a>
    <a href="inventario.php">Inventario</a>
    <a href="intercambios.php">Intercambios<?php if ($pend > 0) echo ' <span class="badge">' . $pend . '</span>'; ?></a>
    <?php if (esAdmin($c, $idUsuario)) echo '<a href="adminPanel.php">Panel de adminstración</a>'; ?>
    <a href="logout.php">Salir</a>
</nav>
    <?php
}

/* =====================================================================
 * 4. CONTROLADOR: decide qué hacer según la URL / el POST
 * ===================================================================== */

/* ---------- 4.a Imagen de carta (antes: carta_img.php) ---------- */
if (isset($_GET['img'])) {
    if (!isset($_SESSION['id'])) {
        http_response_code(403);
        exit();
    }
    $idImg = (int)$_GET['img'];
    $st = $conexion->prepare('SELECT imagen FROM cartas WHERE id_carta = ?');
    $st->bind_param('i', $idImg);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();

    if (!$r) {
        http_response_code(404);
        exit();
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($r['imagen']) ?: 'image/jpeg';
    if (strpos($mime, 'image/') !== 0) {
        $mime = 'image/jpeg';
    }

    session_write_close(); // no bloquear la sesión mientras se envía la imagen
    header('Content-Type: ' . $mime);
    header('Cache-Control: private, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    echo $r['imagen'];
    exit();
}

$yo = requerirLogin();

/* ---------- 4.b Vista "proponer" o vista "listados" ---------- */
$con = (int)($_GET['con'] ?? $_POST['con'] ?? 0);
$modoProponer = $con > 0;

$mensaje = null;
$okMsg = false;
$error = null;

/* ---------- 4.c Procesar POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'proponer') {
        /* --- Enviar una propuesta nueva --- */
        $nombreOtro = $con !== $yo ? usuarioExiste($conexion, $con) : null;
        if ($nombreOtro === null) {
            header('Location: intercambios.php?tab=usuarios');
            exit();
        }
        if (!csrfValido()) {
            $error = 'Sesión inválida, recargá la página.';
        } else {
            [$ok, $msg] = proponerIntercambio(
                $conexion, $yo, $con,
                leerSeleccion($_POST['ofrece'] ?? null),
                leerSeleccion($_POST['pide'] ?? null)
            );
            if ($ok) {
                $_SESSION['flash'] = [true, "Propuesta enviada a $nombreOtro."];
                header('Location: intercambios.php?tab=enviadas');
                exit();
            }
            $error = $msg;
        }
        // si hubo error se sigue a mostrar el formulario con el mensaje
    } else {
        /* --- Aceptar / rechazar / cancelar --- */
        $idInt = (int)($_POST['id_intercambio'] ?? 0);

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
}

/* ---------- 4.d Preparar datos de la vista ---------- */
if (isset($_SESSION['flash'])) {
    [$okMsg, $mensaje] = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

if ($modoProponer) {
    $nombreOtro = $con !== $yo ? usuarioExiste($conexion, $con) : null;
    if ($nombreOtro === null) {
        header('Location: intercambios.php?tab=usuarios');
        exit();
    }
    $miInv = inventarioDe($conexion, $yo);
    $suInv = inventarioDe($conexion, $con);
    $titulo = 'Proponer intercambio';
} else {
    $tab = $_GET['tab'] ?? 'recibidas';
    if (!in_array($tab, ['recibidas', 'enviadas', 'historial', 'usuarios'], true)) {
        $tab = 'recibidas';
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
    $titulo = 'Intercambios';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($titulo) ?></title>
    <link rel="stylesheet" href="style/style.css?v=3">
</head>
<body>

<?php pintarNav($conexion, $yo); ?>

<?php if ($modoProponer): ?>
<!-- =================================================================
     VISTA: PROPONER INTERCAMBIO (antes: proponer.php)
     ================================================================= -->

<div class="cartel"><h1>Intercambio con <?= h($nombreOtro) ?></h1></div>

<?php if ($error): ?>
    <div class="aviso error"><?= h($error) ?></div>
<?php endif; ?>

<form method="post" action="intercambios.php?con=<?= $con ?>" id="form-trade">
    <?= csrfCampo() ?>
    <input type="hidden" name="accion" value="proponer">
    <input type="hidden" name="con" value="<?= $con ?>">

    <div class="trade-wrap">
        <h2 class="sec">Tus cartas (lo que ofrecés)</h2>
        <?php pintarSeleccion($miInv, 'ofrece'); ?>

        <h2 class="sec">Cartas de <?= h($nombreOtro) ?> (lo que pedís)</h2>
        <?php pintarSeleccion($suInv, 'pide'); ?>

        <div class="trade-enviar">
            <p id="resumen" class="ayuda">Ofrecés 0 cartas · Pedís 0 cartas</p>
            <button type="submit" class="boton" id="enviar" disabled>Enviar propuesta</button>
        </div>
    </div>
</form>

<script>
(function () {
    const form = document.getElementById('form-trade');
    const resumen = document.getElementById('resumen');
    const enviar = document.getElementById('enviar');

    function total(prefix) {
        let n = 0;
        form.querySelectorAll('input[name^="' + prefix + '["]').forEach(function (inp) {
            let v = parseInt(inp.value, 10) || 0;
            const max = parseInt(inp.max, 10);
            if (v < 0) v = 0;
            if (v > max) { v = max; inp.value = max; }
            inp.closest('.sel-carta').classList.toggle('elegida', v > 0);
            n += v;
        });
        return n;
    }
    function actualizar() {
        const o = total('ofrece'), p = total('pide');
        resumen.textContent = 'Ofrecés ' + o + ' carta(s) · Pedís ' + p + ' carta(s)';
        enviar.disabled = !(o > 0 && p > 0);
    }
    form.addEventListener('input', actualizar);
    actualizar();
})();
</script>

<?php else: ?>
<!-- =================================================================
     VISTA: LISTADOS (recibidas / enviadas / historial / usuarios)
     ================================================================= -->

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
            <a class="usuario-item" href="intercambios.php?con=<?= (int)$u['id'] ?>">
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

<?php endif; ?>

<script src="script/menu.js"></script>
</body>
</html>
