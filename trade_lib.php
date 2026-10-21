<?php
/*
 * Funciones del sistema de intercambios.
 * Se incluye DESPUÉS de conexion.php y session_start().
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conexion->set_charset('utf8mb4');



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

/* ---------- consultas ---------- */

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
        'SELECT c.id_carta, c.rareza, i.cantidad
           FROM inventario i
           JOIN cartas c ON c.id_carta = i.id_carta
          WHERE i.id_usuario = ? AND i.cantidad > 0
          ORDER BY c.id_carta DESC'
    );
    $st->bind_param('i', $idUsuario);
    $st->execute();
    $out = [];
    foreach ($st->get_result() as $f) {
        $out[(int)$f['id_carta']] = ['rareza' => $f['rareza'], 'cantidad' => (int)$f['cantidad']];
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

/* ---------- acciones ---------- */

const MAX_CARTAS_POR_LADO = 10;

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

        $st = $c->prepare('INSERT INTO intercambio_cartas (id_intercambio, id_carta, lado, cantidad) VALUES (?, ?, ?, ?)');
        foreach (['ofrece' => $ofrece, 'pide' => $pide] as $lado => $lista) {
            foreach ($lista as $idCarta => $cant) {
                $st->bind_param('iisi', $idInt, $idCarta, $lado, $cant);
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

/** Mueve $cant copias de una carta de un usuario a otro. Lanza excepción si no alcanza. */
function transferirCarta(mysqli $c, int $de, int $a, int $idCarta, int $cant): void
{
    $st = $c->prepare('UPDATE inventario SET cantidad = cantidad - ? WHERE id_usuario = ? AND id_carta = ? AND cantidad >= ?');
    $st->bind_param('iiii', $cant, $de, $idCarta, $cant);
    $st->execute();
    if ($st->affected_rows !== 1) {
        throw new RuntimeException('stock');
    }
    $st = $c->prepare('DELETE FROM inventario WHERE id_usuario = ? AND id_carta = ? AND cantidad <= 0');
    $st->bind_param('ii', $de, $idCarta);
    $st->execute();

    $st = $c->prepare('UPDATE inventario SET cantidad = cantidad + ? WHERE id_usuario = ? AND id_carta = ?');
    $st->bind_param('iii', $cant, $a, $idCarta);
    $st->execute();
    if ($st->affected_rows === 0) {
        $st = $c->prepare('INSERT INTO inventario (id_usuario, id_carta, cantidad) VALUES (?, ?, ?)');
        $st->bind_param('iii', $a, $idCarta, $cant);
        $st->execute();
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

        $st = $c->prepare('SELECT id_carta, lado, cantidad FROM intercambio_cartas WHERE id_intercambio = ?');
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
            $cant    = (int)$it['cantidad'];
            if ($it['lado'] === 'ofrece') {
                transferirCarta($c, $emisor, $usuario, $idCarta, $cant);
            } else {
                transferirCarta($c, $usuario, $emisor, $idCarta, $cant);
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
