<?php 
include('conexion.php');
session_start();
$id_user = $_SESSION['id'];

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
               echo "comun ";
            break;

            case ($rareza >= 51 && $rareza <= 65): // especial
               echo "especial ";
            break;

            case ($rareza >= 66 && $rareza <= 75): // epico
               echo "epic ";
            break;

            case ($rareza >= 76 && $rareza <= 80): // legendary
               echo "legendario ";
            break;

            case ($rareza >= 81 && $rareza <= 99): // mithic
               echo "mithic ";
            break;

            case ($rareza == 100): // divine
               echo "divine ";
            break;

            default:
               echo "error de rareza ";
            break;
         }

         $roll = random_int(1, 100);

         switch ($roll) {
            // 75% normal
            case ($roll <= 75):
               echo "normal";
               echo "<br>";
            break;

            // 25% ediciones
            case ($roll <= 85): // 40% de 25
               echo "foil";
               echo "<br>";
            break;

            case ($roll <= 92): // +30%
               echo "holo";
               echo "<br>";
            break;

            case ($roll <= 97): // +20%
               echo "polychrome";
               echo "<br>";
            break;

            case ($roll <= 100): // +10%
               echo "negative";
               echo "<br>";
            break;
         }
         $cartaAmount = $cartaAmount+1; //incrementamos el contador
      }
      echo "</div>";
 
   }
}else{
   $puntosActuales = $resultado['puntos'];
   echo "<p><span id='contador'>".$puntosActuales." </span></p>
            <button class='boton' id='comprar'>Comprar Paquete</button>
            <div id='contenedor'></div>"; //aca van las cartas
   echo "<script>alert('hola')</script>";
}

?>