<?php
$imagen_compartida = 'https://picsum.photos/id/1025/320/446'; 

$mis_cartas = [
    ['nombre' => 'Carta Común',     'rareza' => 'comun'],
    ['nombre' => 'Carta Foil',      'rareza' => 'foil'],
    ['nombre' => 'Carta Holo',      'rareza' => 'holo'],
    ['nombre' => 'Carta Policromo', 'rareza' => 'policromo'],
    ['nombre' => 'Carta Negativo',  'rareza' => 'negativo']
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Esqueleto TCG - Brillo Contenido</title>
    

</head>
<body>

    <div class="grid-tcg">
        <?php foreach ($mis_cartas as $index => $c): ?>
            
            <div class="atropos mi-carta-tcg" data-index="<?php echo $index; ?>">
                <div class="atropos-scale">
                    <div class="atropos-rotate">
                        <div class="atropos-inner">
                            
                            <div class="contenedor-visual" data-atropos-offset="2">
                                <img class="carta-imagen" src="<?php echo $imagen_compartida; ?>" alt="<?php echo $c['nombre']; ?>">
                                <div class="capa-brillo <?php echo $c['rareza']; ?>" id="brillo-<?php echo $index; ?>"></div>
                            </div>

                        </div>
                    </div>
                </div>
                <p style="color: #aaa; text-align: center; margin-top: 15px; font-family: monospace;">
                    [ <?php echo strtoupper($c['rareza']); ?> ]
                </p>
            </div>

        <?php endforeach; ?>
    </div>



</body>
</html>