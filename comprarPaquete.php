<?php 
include('conexion.php');
session_start();
$id_user = $_SESSION['id'];

$rarezaCarta = "";
$rollCarta = "";

//seleccionamos los puntos actuales de la bbdd
$query2 = 'SELECT puntos FROM usuarios WHERE id = "'.$id_user.'"';
$puntosDB = mysqli_query($conexion,$query2);
$resultado = mysqli_fetch_assoc($puntosDB);

//restamos el valor del paquete a los puntos y actualizamos el valor en la BBDD
if($resultado['puntos'] >= 10){
   $puntosActuales = $resultado['puntos']-10;
   $query = 'UPDATE usuarios SET puntos = "'.$puntosActuales.'" WHERE id = "'.$id_user.'"';
   $actualizacionPuntos = mysqli_query($conexion, $query);

   //si la consulta funciono, generamos las cartas
   if ($actualizacionPuntos) {
      //actualizacion de boton
      echo "<p><span id='contador'>".$puntosActuales." </span></p>
            <button class='boton' id='comprar'>Comprar Paquete</button>";
   
      //generacion de 3 cartas

      echo "<div id='contenedor'>";

      $cartaAmount = 0; //contador
      while ($cartaAmount <= 2) { //cantidad de cartas en paquete (3)
          $rareza = random_int(1, 100);

         switch ($rareza) {
            case ($rareza >= 0 && $rareza <= 50): // comun
               $rarezaCarta = "comun ";
            break;

            case ($rareza >= 51 && $rareza <= 65): // especial
               $rarezaCarta = "especial ";
            break;

            case ($rareza >= 66 && $rareza <= 75): // epico
               $rarezaCarta = "epico ";
            break;

            case ($rareza >= 76 && $rareza <= 80): // legendary
               $rarezaCarta = "legendario ";
            break;

            case ($rareza >= 81 && $rareza <= 99): // mithic
               $rarezaCarta = "mithic ";
            break;

            case ($rareza == 100): // divine
               $rarezaCarta = "divine ";
            break;

            default:
               echo "error de rareza ";
            break;
         }

         $roll = random_int(1, 100);

         switch ($roll) {
            // 75% normal
            case ($roll <= 75):
               $rollCarta = "normal";
               echo "<br>";
            break;

            // 25% ediciones
            case ($roll <= 85): // 40% de 25
               $rollCarta = "foil";
               echo "<br>";
            break;

            case ($roll <= 92): // +30%
               $rollCarta = "holo";
               echo "<br>";
            break;

            case ($roll <= 97): // +20%
               $rollCarta = "polychrome";
               echo "<br>";
            break;

            case ($roll <= 100): // +10%
               $rollCarta = "negative";
               echo "<br>";
            break;
         }
         $cartaAmount = $cartaAmount+1; //incrementamos el contador

         $id_img = random_int(0,6); //<-- img random desde la carpeta "cartas"

         //$sql = "SELECT * FROM cartas WHERE rareza = '".$rarezaCarta."' ORDER BY RAND() LIMIT 1"; //<-- id de la imagen random desde la base de datos, el resultado se filtra por rareza

         //$sql = "SELECT * FROM cartas ORDER BY RAND() LIMIT 1"; //<-- id de la imagen random desde la base de datos

         //id_img random desde la base de datos
         //$resultado = mysqli_query($conexion, $sql);
         //$fila = mysqli_fetch_assoc($resultado);
         //$id_img = $fila['id'];

         echo "<div class='carta'>
                  <img src='cartas/".$id_img.".png' alt='Carta ".$id_img."'>
                  <p>Rareza: ".$rarezaCarta."</p>
                  <p>Edición: ".$rollCarta."</p>
               </div>";

      }

 
   }
}

?>