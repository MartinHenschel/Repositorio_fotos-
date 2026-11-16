<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
require_once 'includes/json_response.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || !usuarioTemPermissao('imagens_upload')) {
    http_response_code(403);
    responderJson([
        'sucesso' => false,
        'erro' => 'Não autorizado'
    ]);
}

/**
 * Endpoint de upload usado pelo formulário web (catalogo.js / catalogo.php / upload.php).
 * Recebe: POST multipart com o arquivo "imagem", o token "csrf_token" e, opcionalmente, "album_id".
 * Responde sempre em JSON: {"sucesso": true, "url": "..."} ou {"sucesso": false, "erro": "..."}.
 */

function exigirMetodoPost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderJson(['sucesso' => false, 'erro' => 'Método não permitido, use POST.'], 405);
    }
}

function exigirCsrfValido(): void
{
    $tokenEsperado = $_SESSION['csrf_token'] ?? '';
    $tokenRecebido = $_POST['csrf_token'] ?? '';

    if ($tokenEsperado === '' || !hash_equals($tokenEsperado, $tokenRecebido)) {
        responderJson(['sucesso' => false, 'erro' => 'Sessão expirada, recarregue a página e tente novamente.'], 403);
    }
}

function obterArquivoEnviadoOuFalhar(): array
{
    if (!isset($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
        responderJson(['sucesso' => false, 'erro' => 'Nenhuma imagem válida foi enviada.']);
    }
    return $_FILES['imagem'];
}

function validarImagemOuFalhar(array $arquivo): void
{
    $maxUploadSize = 10 * 1024 * 1024;
    if (!is_uploaded_file($arquivo['tmp_name']) || $arquivo['size'] <= 0 || $arquivo['size'] > $maxUploadSize) {
        responderJson(['sucesso' => false, 'erro' => 'A imagem deve ter entre 1 byte e 10 MB.'], 400);
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
    $mimesPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];
    $imageInfo = @getimagesize($arquivo['tmp_name']);
    if (!in_array($mime, $mimesPermitidos, true) || $imageInfo === false) {
        responderJson(['sucesso' => false, 'erro' => 'O arquivo enviado não é uma imagem válida.']);
    }
    if ($imageInfo[0] > MAX_IMAGE_WIDTH || $imageInfo[1] > MAX_IMAGE_HEIGHT
        || ($imageInfo[0] * $imageInfo[1]) > MAX_IMAGE_PIXELS) {
        responderJson([
            'sucesso' => false,
            'erro' => 'Dimensões máximas permitidas: ' . MAX_IMAGE_WIDTH . 'x' . MAX_IMAGE_HEIGHT . ' pixels.',
        ], 400);
    }
}

/**
 * Álbum de destino (opcional). Se vier vazio ou não vier, a foto entra sem álbum.
 * Confirma que o álbum realmente existe antes de aceitar, senão ignora (evita FK "fantasma").
 */
function obterAlbumIdDestinoOuFalhar(): ?int
{
    if (!isset($_POST['album_id']) || $_POST['album_id'] === '') {
        return null;
    }

    $albumId = (int) $_POST['album_id'];
    if (!getAlbum($albumId)) {
        responderJson(['sucesso' => false, 'erro' => 'Álbum informado não existe.']);
    }

    return $albumId;
}

function obterTituloDaImagem(array $arquivo): string
{
    $nomeOriginal = pathinfo($arquivo['name'], PATHINFO_FILENAME);
    return trim($_POST['titulo'] ?? '') ?: $nomeOriginal;
}

// ========== Fluxo do endpoint ==========

exigirMetodoPost();
exigirCsrfValido();

$arquivo = obterArquivoEnviadoOuFalhar();
validarImagemOuFalhar($arquivo);

$albumId = obterAlbumIdDestinoOuFalhar();
$titulo = obterTituloDaImagem($arquivo);

$upload = uploadImage($titulo, $albumId, $arquivo);
if ($upload !== false) {
    responderJson([
        'sucesso' => true,
        'id' => $upload['id'],
        'url' => rtrim(BASE_URL, '/') . '/' . $upload['url'],
    ]);
} else {
    responderJson(['sucesso' => false, 'erro' => 'Falha ao salvar a imagem no servidor.']);
}