<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/json_response.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || !usuarioTemPermissao('imagens_upload')) {
    http_response_code(403);
    responderJson(['sucesso' => false, 'erro' => 'Não autorizado']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderJson(['sucesso' => false, 'erro' => 'Método não permitido, use GET.'], 405);
}

$albuns = getAllAlbums();

$albunsFormatados = array_map(function ($album) {
    return [
        'id'   => (int) $album['id'],
        'nome' => $album['nome'],
    ];
}, $albuns);

responderJson([
    'sucesso' => true,
    'albuns'  => $albunsFormatados,
]);