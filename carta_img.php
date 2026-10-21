<?php
// esto es para evitar poner todoel codigo de las imagenes siempre
include('conexion.php');
session_start();

if (!isset($_SESSION['id'])) {
    http_response_code(403);
    exit();
}

$id = (int)($_GET['id'] ?? 0);
$st = $conexion->prepare('SELECT imagen FROM cartas WHERE id_carta = ?');
$st->bind_param('i', $id);
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
