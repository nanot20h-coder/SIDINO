<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

require_once 'cong.php';

$datos = json_decode(file_get_contents('php://input'), true);

if (empty($datos)) {
    $datos = $_POST;
}

$colegio   = trim($datos['colegio']   ?? '');
$direccion = trim($datos['direccion'] ?? '');
$email     = trim($datos['email']     ?? '');
$telefono  = trim($datos['telefono']  ?? '');

$errores = [];

if (empty($colegio))                           $errores[] = 'El nombre del colegio es obligatorio.';
if (empty($direccion))                         $errores[] = 'La dirección es obligatoria.';
if (empty($email))                             $errores[] = 'El correo electrónico es obligatorio.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo electrónico no es válido.';
if (empty($telefono))                          $errores[] = 'El teléfono es obligatorio.';

if (!empty($errores)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errores' => $errores]);
    exit;
}

$conn = conectar();

$stmt = $conn->prepare(
    "INSERT INTO solicitudes (colegio, direccion, email, telefono)
     VALUES (?, ?, ?, ?)"
);

$stmt->bind_param('ssss', $colegio, $direccion, $email, $telefono);

if ($stmt->execute()) {
    echo json_encode([
        'ok'      => true,
        'mensaje' => '¡Solicitud recibida! Pronto te contactaremos en tu correo.',
        'id'      => $stmt->insert_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo guardar la solicitud.']);
}

$stmt->close();
$conn->close();