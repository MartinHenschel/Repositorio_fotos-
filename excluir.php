<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
requirePermission('imagens_excluir');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido.');
}

verificarCsrfOuFalhar();

if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    deleteImage($id);
}

// Redirecionar de volta para a página anterior
$ref = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header('Location: ' . $ref);
exit;
?>