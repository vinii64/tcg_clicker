<?php
include('conexion.php');
session_start();
include('trade_lib.php');

$yo = requerirLogin();
$con = (int)($_GET['con'] ?? $_POST['con'] ?? 0);
$nombreOtro = $con > 0 && $con !== $yo ? usuarioExiste($conexion, $con) : null;

if ($nombreOtro === null) {
    header('Location: intercambios.php?tab=usuarios');
    exit();
}

$error = null;

/** Normaliza el input ofrece[id]=cant a [id => cant] con enteros positivos. */
function leerSeleccion($raw): array
{
    $out = [];
    if (is_array($raw)) {
        foreach ($raw as $id => $q) {
            $id = (int)$id;
            $q = (int)$q;
            if ($id > 0 && $q > 0) {
                $out[$id] = $q;
            }
        }
    }
    return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}

$miInv = inventarioDe($conexion, $yo);
$suInv = inventarioDe($conexion, $con);

function pintarSeleccion(array $inv, string $campo): void
{
    if (!$inv) {
        echo '<p class="vacio">No hay cartas disponibles.</p>';
        return;
    }
    echo '<div class="inventario sel-grid">';
    foreach ($inv as $id => $d) {
        $max = (int)$d['cantidad'];
        echo '<label class="carta sel-carta">'
           . '<img src="carta_img.php?id=' . (int)$id . '" alt="Carta" loading="lazy">'
           . '<div class="carta-info"><p>Rareza: <span>' . h(nombreRareza($d['rareza'])) . '</span></p>'
           . '<p>Disponibles: <span>x' . $max . '</span></p></div>'
           . '<div class="sel-cant"><span>Cantidad</span>'
           . '<input type="number" name="' . h($campo) . '[' . (int)$id . ']" min="0" max="' . $max . '" value="0"></div>'
           . '</label>';
    }
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proponer intercambio</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>

<?php include('nav.php'); ?>

<div class="cartel"><h1>Intercambio con <?= h($nombreOtro) ?></h1></div>

<?php if ($error): ?>
    <div class="aviso error"><?= h($error) ?></div>
<?php endif; ?>

<form method="post" action="proponer.php?con=<?= $con ?>" id="form-trade">
    <?= csrfCampo() ?>
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
</body>
</html>
