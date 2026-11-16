<?php
// download_album.php
// Gera uma planilha .xlsx com uma única aba URL para um álbum.
//
// Requisitos: phpoffice/phpspreadsheet (composer require phpoffice/phpspreadsheet)

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
requirePermission('imagens_baixar');

require_once __DIR__ . '/vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$albumId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($albumId <= 0) {
    http_response_code(400);
    exit('Álbum inválido.');
}

// -----------------------------------------------------------------
// Busca imagens APENAS pelas funções oficiais do app
// -----------------------------------------------------------------
$images = [];

if (function_exists('getImagesByAlbumPaginated')) {
    $p = getImagesByAlbumPaginated($albumId, 1, 10000);
    if (is_array($p) && isset($p['images']) && is_array($p['images'])) {
        $images = $p['images'];
    }
} elseif (function_exists('getImagesByAlbum')) {
    $result = getImagesByAlbum($albumId);
    if (is_array($result)) {
        $images = $result;
    }
} elseif (function_exists('getImages')) {
    $result = getImages($albumId);
    if (is_array($result)) {
        $images = $result;
    }
}

// host base para montar links
$host = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

// -----------------------------------------------------------------
// Monta planilha com PhpSpreadsheet
// -----------------------------------------------------------------
$spreadsheet = new Spreadsheet();

// Se não encontrou imagens, cria planilha com aviso
if (empty($images)) {
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('URL');
    $sheet->setCellValue('A1', 'Referência');
    $sheet->setCellValue('B1', 'URL Pública');
    $sheet->setCellValue('A2', 'Nenhuma imagem encontrada para este álbum.');
    $writer = new Xlsx($spreadsheet);

    $filename = "album-{$albumId}.xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');

    while (ob_get_level() > 0) { ob_end_clean(); }
    $writer->save('php://output');
    exit;
}

// Aba única: URL pública direta
$sheet3 = $spreadsheet->getActiveSheet();
$sheet3->setTitle('URL');
$sheet3->setCellValue('A1', 'Referência');
$sheet3->setCellValue('B1', 'URL Pública');
$row = 2;
foreach ($images as $img) {
    $ref = $img['nome'] ?? ($img['filename'] ?? ($img['name'] ?? ''));
    $url = $img['url'] ?? '';
    if ($url && !preg_match('#^https?://#i', $url)) {
        $url = $host . '/' . ltrim($url, '/');
    } elseif (!$url && !empty($img['filepath'])) {
        $url = $host . '/' . ltrim($img['filepath'], '/');
    }
    $sheet3->setCellValue("A{$row}", $ref);
    $sheet3->setCellValue("B{$row}", $url);
    $row++;
}
$sheet3->getColumnDimension('A')->setAutoSize(true);
$sheet3->getColumnDimension('B')->setWidth(80);

$spreadsheet->setActiveSheetIndex(0);

$writer = new Xlsx($spreadsheet);
$filename = 'album-' . $albumId . '-' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');

while (ob_get_level() > 0) { ob_end_clean(); }
$writer->save('php://output');
exit;