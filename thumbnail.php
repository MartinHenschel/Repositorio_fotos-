<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requirePermission('imagens_ver');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(400);
    exit('Imagem inválida.');
}

$stmt = getDB()->prepare('SELECT url FROM images WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$image = $stmt->fetch(PDO::FETCH_ASSOC);
$url = $image['url'] ?? '';

if (!str_starts_with($url, 'uploads/')) {
    http_response_code(404);
    exit('Imagem não encontrada.');
}

$sourcePath = realpath(UPLOAD_DIR . basename($url));
$uploadRoot = realpath(UPLOAD_DIR);
if (!$sourcePath || !$uploadRoot || !str_starts_with($sourcePath, $uploadRoot . DIRECTORY_SEPARATOR) || !is_file($sourcePath)) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$thumbnailDir = STORAGE_DIR . 'thumbnails/';
if (!is_dir($thumbnailDir)) {
    mkdir($thumbnailDir, 0755, true);
}
$thumbnailPath = $thumbnailDir . $id . '.jpg';

if (!is_file($thumbnailPath) || filemtime($thumbnailPath) < filemtime($sourcePath)) {
    $imageInfo = getimagesize($sourcePath);
    if ($imageInfo === false) {
        http_response_code(415);
        exit('Formato de imagem não suportado.');
    }

    $source = match ($imageInfo['mime']) {
        'image/jpeg' => imagecreatefromjpeg($sourcePath),
        'image/png' => imagecreatefrompng($sourcePath),
        'image/gif' => imagecreatefromgif($sourcePath),
        'image/bmp' => function_exists('imagecreatefrombmp') ? imagecreatefrombmp($sourcePath) : false,
        'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($sourcePath) : false,
        default => false,
    };

    if (!$source) {
        http_response_code(415);
        exit('Formato de imagem não suportado.');
    }

    $width = imagesx($source);
    $height = imagesy($source);
    $scale = min(480 / $width, 360 / $height, 1);
    $newWidth = max(1, (int)round($width * $scale));
    $newHeight = max(1, (int)round($height * $scale));
    $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagejpeg($thumbnail, $thumbnailPath, 82);
    imagedestroy($thumbnail);
    imagedestroy($source);
}

header('Content-Type: image/jpeg');
header('Cache-Control: private, max-age=86400');
header('Content-Length: ' . filesize($thumbnailPath));
readfile($thumbnailPath);
