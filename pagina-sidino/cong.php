<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pagina_sidino');

function conectar() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error de conexión a la base de datos.']);
        exit;
    }

    return $conn;
}