<?php

header("Content-Type: application/json; charset=UTF-8");

include("conexion.php");

if (!$conexion) {
    http_response_code(500);
    echo json_encode([
        "error" => "Error de conexión"
    ]);
    exit;
}

mysqli_set_charset($conexion, "utf8");

$metodo = $_SERVER["REQUEST_METHOD"];
$recurso = $_GET["recurso"] ?? "";

$datos = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($datos)) {
    $datos = [];
}


/*
====================================================
GET
====================================================
*/

if ($metodo === "GET") {

    // ================================================
    // USUARIOS
    // ================================================

    if ($recurso === "usuarios") {

        if (isset($_GET["id"])) {

            $id = intval($_GET["id"]);

            $sql = "SELECT id, email, usuario, admin, contrasenia, puntos
                    FROM usuarios
                    WHERE id = $id";

        } else {

            $sql = "SELECT id, email, usuario, admin, contrasenia, puntos
                    FROM usuarios";
        }

        $resultado = mysqli_query($conexion, $sql);

        if (!$resultado) {

            http_response_code(500);

            echo json_encode([
                "error" => mysqli_error($conexion)
            ]);

            exit;
        }

        $usuarios = [];

        while ($fila = mysqli_fetch_assoc($resultado)) {
            $usuarios[] = $fila;
        }

        echo json_encode($usuarios);

        exit;
    }


    // ================================================
    // CARTAS
    // ================================================

    if ($recurso === "cartas") {

        if (isset($_GET["id_carta"])) {

            $id_carta = intval($_GET["id_carta"]);

            $sql = "SELECT id_carta, rareza
                    FROM cartas
                    WHERE id_carta = $id_carta";

        } else {

            $sql = "SELECT id_carta, rareza
                    FROM cartas";
        }

        $resultado = mysqli_query($conexion, $sql);

        if (!$resultado) {

            http_response_code(500);

            echo json_encode([
                "error" => mysqli_error($conexion)
            ]);

            exit;
        }

        $cartas = [];

        while ($fila = mysqli_fetch_assoc($resultado)) {
            $cartas[] = $fila;
        }

        echo json_encode($cartas);

        exit;
    }


    // ================================================
    // INVENTARIO
    // ================================================

    if ($recurso === "inventario") {

        $id_usuario = $_GET["id_usuario"] ?? null;
        $id_carta = $_GET["id_carta"] ?? null;


        // Usuario + carta
        if ($id_usuario !== null && $id_carta !== null) {

            $id_usuario = intval($id_usuario);
            $id_carta = intval($id_carta);

            $sql = "SELECT id_carta, id_usuario, cantidad
                    FROM inventario
                    WHERE id_usuario = $id_usuario
                    AND id_carta = $id_carta";
        }


        // Solo usuario
        elseif ($id_usuario !== null) {

            $id_usuario = intval($id_usuario);

            $sql = "SELECT id_carta, id_usuario, cantidad
                    FROM inventario
                    WHERE id_usuario = $id_usuario";
        }


        // Solo carta
        elseif ($id_carta !== null) {

            $id_carta = intval($id_carta);

            $sql = "SELECT id_carta, id_usuario, cantidad
                    FROM inventario
                    WHERE id_carta = $id_carta";
        }


        // Todo
        else {

            $sql = "SELECT id_carta, id_usuario, cantidad
                    FROM inventario";
        }


        $resultado = mysqli_query($conexion, $sql);

        if (!$resultado) {

            http_response_code(500);

            echo json_encode([
                "error" => mysqli_error($conexion)
            ]);

            exit;
        }

        $inventario = [];

        while ($fila = mysqli_fetch_assoc($resultado)) {
            $inventario[] = $fila;
        }

        echo json_encode($inventario);

        exit;
    }


    // Recurso incorrecto
    http_response_code(400);

    echo json_encode([
        "error" => "Recurso no válido"
    ]);

    exit;
}


/*
====================================================
POST
====================================================
*/

if ($metodo === "POST") {


    // ================================================
    // CREAR USUARIO
    // ================================================

    if ($recurso === "usuarios") {

        if (
            !isset($datos["email"]) ||
            !isset($datos["usuario"]) ||
            !isset($datos["contrasenia"])
        ) {

            http_response_code(400);

            echo json_encode([
                "error" => "Faltan datos"
            ]);

            exit;
        }

        $email = $datos["email"];
        $usuario = $datos["usuario"];
        $admin = $datos["admin"] ?? 0;
        $contrasenia = $datos["contrasenia"];
        $puntos = $datos["puntos"] ?? 0;


        $stmt = mysqli_prepare(
            $conexion,
            "INSERT INTO usuarios
            (email, usuario, admin, contrasenia, puntos)
            VALUES (?, ?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssisi",
            $email,
            $usuario,
            $admin,
            $contrasenia,
            $puntos
        );


        if (mysqli_stmt_execute($stmt)) {

            http_response_code(201);

            echo json_encode([
                "mensaje" => "Usuario creado correctamente",
                "id" => mysqli_insert_id($conexion)
            ]);

        } else {

            http_response_code(500);

            echo json_encode([
                "error" => mysqli_error($conexion)
            ]);
        }

        exit;
    }


    // ================================================
    // CREAR CARTA
    // ================================================

    if ($recurso === "cartas") {

        if (!isset($datos["rareza"])) {

            http_response_code(400);

            echo json_encode([
                "error" => "Falta rareza"
            ]);

            exit;
        }

        $rareza = $datos["rareza"];


        $stmt = mysqli_prepare(
            $conexion,
            "INSERT INTO cartas (rareza)
             VALUES (?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $rareza
        );


        if (mysqli_stmt_execute($stmt)) {

            http_response_code(201);

            echo json_encode([
                "mensaje" => "Carta creada correctamente",
                "id_carta" => mysqli_insert_id($conexion)
            ]);

        } else {

            http_response_code(500);

            echo json_encode([
                "error" => mysqli_error($conexion)
            ]);
        }

        exit;
    }


    // ================================================
    // CREAR INVENTARIO
    // ================================================

    if ($recurso === "inventario") {

        if (
            !isset($datos["id_carta"]) ||
            !isset($datos["id_usuario"]) ||
            !isset($datos["cantidad"])
        ) {

            http_response_code(400);

            echo json_encode([
                "error" => "Faltan datos"
            ]);

            exit;
        }

        $id_carta = $datos["id_carta"];
        $id_usuario = $datos["id_usuario"];
        $cantidad = $datos["cantidad"];


        $stmt = mysqli_prepare(
            $conexion,
            "INSERT INTO inventario
            (id_carta, id_usuario, cantidad)
            VALUES (?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "iii",
            $id_carta,
            $id_usuario,
            $cantidad
        );


        if (mysqli_stmt_execute($stmt)) {

            http_response_code(201);

            echo json_encode([
                "mensaje" => "Inventario creado correctamente"
            ]);

        } else {

            http_response_code(500);

            echo json_encode([
                "error" => mysqli_error($conexion)
            ]);
        }

        exit;
    }


    http_response_code(400);

    echo json_encode([
        "error" => "Recurso no válido"
    ]);

    exit;
}


/*
====================================================
PUT
====================================================
*/

if ($metodo === "PUT") {


    // ================================================
    // ACTUALIZAR USUARIO
    // ================================================

    if ($recurso === "usuarios") {

        if (!isset($datos["id"])) {

            http_response_code(400);

            echo json_encode([
                "error" => "Falta id"
            ]);

            exit;
        }

        $id = intval($datos["id"]);

        $email = $datos["email"] ?? "";
        $usuario = $datos["usuario"] ?? "";
        $admin = $datos["admin"] ?? 0;
        $contrasenia = $datos["contrasenia"] ?? "";
        $puntos = $datos["puntos"] ?? 0;


        $stmt = mysqli_prepare(
            $conexion,
            "UPDATE usuarios
             SET email = ?,
                 usuario = ?,
                 admin = ?,
                 contrasenia = ?,
                 puntos = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssisii",
            $email,
            $usuario,
            $admin,
            $contrasenia,
            $puntos,
            $id
        );


        if (mysqli_stmt_execute($stmt)) {

            echo json_encode([
                "mensaje" => "Usuario actualizado correctamente"
            ]);

        } else {

            http_response_code(500);

            echo json_encode([
                "error" => mysqli_error($conexion)
            ]);
        }

        exit;
    }


    // ================================================
    // ACTUALIZAR CARTA
    // ================================================

    if ($recurso === "cartas") {

        if (
            !isset($datos["id_carta"]) ||
            !isset($datos["rareza"])
        ) {

            http_response_code(400);

            echo json_encode([
                "error" => "Faltan datos"
            ]);

            exit;
        }

        $id_carta = intval($datos["id_carta"]);
        $rareza = $datos["rareza"];


        $stmt = mysqli_prepare(
            $conexion,
            "UPDATE cartas
             SET rareza = ?
             WHERE id_carta = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $rareza,
            $id_carta
        );


        if (mysqli_stmt_execute($stmt)) {

            echo json_encode([
                "mensaje" => "Carta actualizada correctamente"
            ]);

        } else {

            http_response_code(500);

            echo json_encode([
                "error" => mysqli_error($conexion)
            ]);
        }

        exit;
    }


    // ================================================
    // ACTUALIZAR INVENTARIO
    // ================================================

    if ($recurso === "inventario") {

        if (
            !isset($datos["id_carta"]) ||
            !isset($datos["id_usuario"]) ||
            !isset($datos["cantidad"])
        ) {

            http_response_code(400);

            echo json_encode([
                "error" => "Faltan datos"
            ]);

            exit;
        }

        $id_carta = intval($datos["id_carta"]);
        $id_usuario = intval($datos["id_usuario"]);
        $cantidad = intval($datos["cantidad"]);


        $stmt = mysqli_prepare(
            $conexion,
            "UPDATE inventario
             SET cantidad = ?
             WHERE id_carta = ?
             AND id_usuario = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "iii",
            $cantidad,
            $id_carta,
            $id_usuario
        );


        if (mysqli_stmt_execute($stmt)) {

            echo json_encode([
                "mensaje" => "Inventario actualizado correctamente"
            ]);

        } else {

            http_response_code(500);

            echo json_encode([
                "error" => mysqli_error($conexion)
            ]);
        }

        exit;
    }


    http_response_code(400);

    echo json_encode([
        "error" => "Recurso no válido"
    ]);

    exit;
}


/*
====================================================
DELETE
====================================================
*/

if ($metodo === "DELETE") {


    // ================================================
    // ELIMINAR USUARIO
    // ================================================

    if ($recurso === "usuarios") {

        if (!isset($datos["id"])) {

            http_response_code(400);

            echo json_encode([
                "error" => "Falta id"
            ]);

            exit;
        }

        $id = intval($datos["id"]);


        $stmt = mysqli_prepare(
            $conexion,
            "DELETE FROM usuarios
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $id
        );


        if (mysqli_stmt_execute($stmt)) {

            echo json_encode([
                "mensaje" => "Usuario eliminado correctamente"
            ]);

        } else {

            http_response_code(500);

            echo json_encode([
                "error" => mysqli_error($conexion)
            ]);
        }

        exit;
    }


    // ================================================
    // ELIMINAR CARTA
    // ================================================

    if ($recurso === "cartas") {

        if (!isset($datos["id_carta"])) {

            http_response_code(400);

            echo json_encode([
                "error" => "Falta id_carta"
            ]);

            exit;
        }

        $id_carta = intval($datos["id_carta"]);


        $stmt = mysqli_prepare(
            $conexion,
            "DELETE FROM cartas
             WHERE id_carta = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $id_carta
        );


        if (mysqli_stmt_execute($stmt)) {

            echo json_encode([
                "mensaje" => "Carta eliminada correctamente"
            ]);

        } else {

            http_response_code(500);

            echo json_encode([
                "error" => mysqli_error($conexion)
            ]);
        }

        exit;
    }


    // ================================================
    // ELIMINAR INVENTARIO
    // ================================================

    if ($recurso === "inventario") {

        if (
            !isset($datos["id_carta"]) ||
            !isset($datos["id_usuario"])
        ) {

            http_response_code(400);

            echo json_encode([
                "error" => "Faltan id_carta o id_usuario"
            ]);

            exit;
        }

        $id_carta = intval($datos["id_carta"]);
        $id_usuario = intval($datos["id_usuario"]);


        $stmt = mysqli_prepare(
            $conexion,
            "DELETE FROM inventario
             WHERE id_carta = ?
             AND id_usuario = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $id_carta,
            $id_usuario
        );


        if (mysqli_stmt_execute($stmt)) {

            echo json_encode([
                "mensaje" => "Inventario eliminado correctamente"
            ]);

        } else {

            http_response_code(500);

            echo json_encode([
                "error" => mysqli_error($conexion)
            ]);
        }

        exit;
    }


    http_response_code(400);

    echo json_encode([
        "error" => "Recurso no válido"
    ]);

    exit;
}


/*
====================================================
MÉTODO NO PERMITIDO
====================================================
*/

http_response_code(405);

echo json_encode([
    "error" => "Método no permitido"
]);

mysqli_close($conexion);

?>
