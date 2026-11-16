<?php
/**
 * Helper compartilhado para respostas JSON das rotas de API.
 * Extraído de api_albums.php e api_upload.php, onde a mesma função
 * estava duplicada (violação do princípio DRY).
 */

function responderJson(array $dados, int $codigoHttp = 200): void
{
    http_response_code($codigoHttp);
    echo json_encode($dados);
    exit;
}
