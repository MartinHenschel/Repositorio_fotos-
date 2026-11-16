<?php
// download.php
// Força download de um arquivo de imagem pelo id.
// Deve validar autenticação e que o arquivo realmente pertence ao registro.

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
requireLogin();
requirePermission('imagens_baixar');

// (Descomente durante debug se precisar ver erros na tela)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}

// Usa a conexão centralizada do projeto.
$pdoConn = getDB();

$sql = "SELECT id, url, nome, album_id FROM images WHERE id = :id LIMIT 1";
$stmt = $pdoConn->prepare($sql);
$stmt->execute(['id' => $id]);
$image = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$image) {
    http_response_code(404);
    exit('Imagem não encontrada.');
}

$relPath = $image['url'] ?? '';
if (!$relPath || !preg_match('#^uploads/[^/\\]+$#', $relPath)) {
    http_response_code(404);
    exit('Caminho do arquivo ausente.');
}

$baseDir = realpath(UPLOAD_DIR);
$fileName = basename($relPath);
$fullpath = $baseDir ? realpath($baseDir . DIRECTORY_SEPARATOR . $fileName) : false;

// Resolve caminho real e previne directory traversal
if (!$fullpath || !$baseDir || !is_file($fullpath)
    || !str_starts_with($fullpath, $baseDir . DIRECTORY_SEPARATOR)) {
    http_response_code(403);
    exit('Acesso negado.');
}

// Nome do arquivo para download
$ext = pathinfo($fullpath, PATHINFO_EXTENSION);
$downloadName = ($image['nome'] ?? pathinfo($fullpath, PATHINFO_FILENAME)) . ($ext ? '.' . $ext : '');

// Envia headers e força download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($fullpath));

// Limpa buffers antes de enviar
if (ob_get_length()) ob_end_clean();

readfile($fullpath);
exit;