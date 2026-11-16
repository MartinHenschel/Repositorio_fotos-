<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
require_once 'includes/json_response.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['sucesso' => false, 'erro' => 'Método não permitido, use POST.'], 405);
}

requirePermission('albuns_criar');
verificarCsrfOuFalhar();

$nome = trim((string)($_POST['nome'] ?? ''));
if ($nome === '') {
    responderJson(['sucesso' => false, 'erro' => 'Informe o nome do álbum.'], 400);
}
if (mb_strlen($nome) > 120) {
    responderJson(['sucesso' => false, 'erro' => 'O nome do álbum deve ter no máximo 120 caracteres.'], 400);
}

try {
    $id = createAlbum($nome);
    responderJson([
        'sucesso' => true,
        'album' => ['id' => (int)$id, 'nome' => $nome],
    ]);
} catch (Throwable $e) {
    error_log('Falha ao criar álbum pelo upload: ' . $e->getMessage());
    responderJson(['sucesso' => false, 'erro' => 'Não foi possível criar o álbum.'], 500);
}
