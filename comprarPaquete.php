<?php
include('conexion.php');
session_start();

$id_user = $_SESSION['id'];

// Seleccionamos los puntos actuales de la BBDD
$query2 = "SELECT puntos FROM usuarios WHERE id = '$id_user'";
$puntosDB = mysqli_query($conexion, $query2);
$resultado = mysqli_fetch_assoc($puntosDB);

// Restamos el valor del paquete a los puntos y actualizamos el valor en la BBDD
if ($resultado['puntos'] >= 10) {

    $puntosActuales = $resultado['puntos'] - 10;

    $query = "UPDATE usuarios SET puntos = '$puntosActuales' WHERE id = '$id_user'";
    $actualizacionPuntos = mysqli_query($conexion, $query);

    // Si la consulta funcionó, generamos las cartas
    if ($actualizacionPuntos) {

        echo "
        <p><span id='contador'>$puntosActuales</span></p>
        <button class='boton' id='comprar'>Comprar Paquete</button>
        ";

        echo "<div class='inventario'>";

        // Generación de 3 cartas
        for ($cartaAmount = 0; $cartaAmount < 3; $cartaAmount++) {

            $rareza = random_int(1, 100);

            // Rareza
            if ($rareza <= 50) {
                $rarezaCarta = "Comín";
            } elseif ($rareza <= 65) {
                $rarezaCarta = "Especial";
            } elseif ($rareza <= 75) {
                $rarezaCarta = "Épica";
            } elseif ($rareza <= 80) {
                $rarezaCarta = "Legendaria";
            } elseif ($rareza <= 99) {
                $rarezaCarta = "Mítica";
            } else {
                $rarezaCarta = "Divina";
            }

            // Edición
            $roll = random_int(1, 100);

            if ($roll <= 75) {
                $rollCarta = "Normal";
            } elseif ($roll <= 85) {
                $rollCarta = "Brillante";
            } elseif ($roll <= 92) {
                $rollCarta = "Holográfica";
            } elseif ($roll <= 97) {
                $rollCarta = "Polícroma";
            } else {
                $rollCarta = "Negativa";
            }

            // Obtener carta aleatoria de la rareza correspondiente
            $sql = "SELECT * FROM cartas WHERE rareza = '$rarezaCarta' ORDER BY RAND() LIMIT 1";
            $resultadoCarta = mysqli_query($conexion, $sql);
            $carta = mysqli_fetch_assoc($resultadoCarta);

            if ($carta) {
                echo "
                
                  <div class='carta'>
                     <div class='carta-imagen'>
                           <img src='data:image/jpeg;base64," . base64_encode($carta['imagen']) . "'>
                     </div>

                     <div class='carta-info'>
                           <p>Rareza: <span>$rarezaCarta</span></p>
                           <p>Edición: <span>$rollCarta</span></p>
                     </div>
                  </div>
                
                ";
            } else {
                echo "
                <div class='carta'>
                    <div class='carta-info'>
                        <p>No existe ninguna carta $rarezaCarta</p>
                    </div>
                </div>
                ";
            }
        } 

        echo "</div>";
    }

} else {

    echo "
    <p><span id='contador'>{$resultado['puntos']}</span></p>
    <button class='boton' id='comprar'>Comprar Paquete</button>
    <p>No tienes suficientes puntos.</p>
    ";
}
?>