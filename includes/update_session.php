<?php
require_once 'config.php';
require_once 'auth.php';

// Simplesmente chamar isLoggedIn() atualiza $_SESSION['last_activity']
if (isLoggedIn()) {
    http_response_code(200);
} else {
    http_response_code(401);
}

// Retornar JSON
header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'time_remaining' => getSessionTimeRemaining()]);
?>