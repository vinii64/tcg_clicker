<?php

header("Content-Type: application/json");

// =====================================
// CONEXIÓN A LA BASE DE DATOS
// =====================================

include('conexion.php');


if (!$conexion) {

    http_response_code(500);

    echo json_encode([
        "error" => "Error de conexión"
    ]);

    exit;
}

mysqli_set_charset($conexion, "utf8");

// =====================================
// DATOS DE LA PETICIÓN
// =====================================

$metodo = $_SERVER["REQUEST_METHOD"];

$recurso = $_GET["recurso"] ?? "";

$datos = json_decode(
    file_get_contents("php://input"),
    true
);

// =====================================
// CRUD
// =====================================

switch ($metodo) {

    // =================================
    // GET
    // =================================

    case "GET":

        switch ($recurso) {

            case "usuarios":

                $sql = "SELECT * FROM usuarios";

                $resultado = mysqli_query(
                    $conexion,
                    $sql
                );

                $usuarios = [];

                while ($fila = mysqli_fetch_assoc($resultado)) {

                    $usuarios[] = $fila;
                }

                echo json_encode($usuarios);

                break;


            case "cartas":

                $sql = "SELECT * FROM cartas";

                $resultado = mysqli_query(
                    $conexion,
                    $sql
                );

                $cartas = [];

                while ($fila = mysqli_fetch_assoc($resultado)) {

                    $cartas[] = $fila;
                }

                echo json_encode($cartas);

                break;


            default:

                http_response_code(400);

                echo json_encode([
                    "error" => "Recurso no válido"
                ]);

                break;
        }

        break;


    // =================================
    // POST
    // =================================

    case "POST":

        switch ($recurso) {

            case "usuarios":

                $usuario = $datos["usuario"];
                $email = $datos["email"];
                $contrasenia = $datos["contrasenia"];

                $sql = "INSERT INTO usuarios (usuario, email, contrasenia) VALUES ('$usuario', '$email', '$contrasenia')";

                $resultado = mysqli_query(
                    $conexion,
                    $sql
                );

                if ($resultado) {

                    echo json_encode([
                        "mensaje" => "Usuario creado correctamente"
                    ]);

                } else {

                    http_response_code(500);

                    echo json_encode([
                        "error" => "No se pudo crear el usuario"
                    ]);
                }

                break;

//PONER PARA SUBIR FOTOS DE CARTA API BLOB CHATGPT 

            case "cartas":

                $rareza = $datos["rareza"];


                $sql = "INSERT INTO cartas (rareza) VALUES ('$rareza')";

                $resultado = mysqli_query(
                    $conexion,
                    $sql
                );

                if ($resultado) {

                    echo json_encode([
                        "mensaje" => "carta creada correctamente"
                    ]);

                } else {

                    http_response_code(500);

                    echo json_encode([
                        "error" => "no se pudo crear la carta"
                    ]);
                }

                break;


            default:

                http_response_code(400);

                echo json_encode([
                    "error" => "Recurso no válido"
                ]);

                break;
        }

        break;


    // =================================
    // PUT
    // =================================

    case "PUT":

        switch ($recurso) {

            case "usuarios":

                $id = $datos["id"];
                $nombre = $datos["nombre"];
                $email = $datos["email"];
                $puntos = $datos["puntos"];

                $sql = "UPDATE usuarios SET nombre = '$nombre', email = '$email', puntos = '$puntos' WHERE id = $id";

                $resultado = mysqli_query(
                    $conexion,
                    $sql
                );

                echo json_encode([
                    "mensaje" => "usuario actualizado correctamente"
                ]);

                break;


            case "cartas":

                $id = $datos["id"];
                $rareza = $datos["rareza"];


                $sql = "UPDATE cartas SET rareza = '$rareza' WHERE id = $id";

                $resultado = mysqli_query(
                    $conexion,
                    $sql
                );

                echo json_encode([
                    "mensaje" => "carta actualizada correctamente"
                ]);

                break;


            default:

                http_response_code(400);

                echo json_encode([
                    "error" => "Recurso no válido"
                ]);

                break;
        }

        break;


    // =================================
    // DELETE
    // =================================

    case "DELETE":

        switch ($recurso) {

            case "usuarios":

                $id = $datos["id"];

                $sql = "DELETE FROM usuarios WHERE id = $id";

                $resultado = mysqli_query(
                    $conexion,
                    $sql
                );

                echo json_encode([
                    "mensaje" => "usuario eliminado correctamente"
                ]);

                break;


            case "cartas":

                $id = $datos["id"];

                $sql = "DELETE FROM cartas WHERE id = $id";

                $resultado = mysqli_query(
                    $conexion,
                    $sql
                );

                echo json_encode([
                    "mensaje" => "Producto eliminado correctamente"
                ]);

                break;


            default:

                http_response_code(400);

                echo json_encode([
                    "error" => "Recurso no válido"
                ]);

                break;
        }

        break;


    // =================================
    // MÉTODO NO PERMITIDO
    // =================================

    default:

        http_response_code(405);

        echo json_encode([
            "error" => "Método no permitido"
        ]);

        break;
}

// =====================================
// CERRAR CONEXIÓN
// =====================================

mysqli_close($conexion);

?>