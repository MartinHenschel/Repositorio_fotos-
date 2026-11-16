<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Execute este arquivo pelo terminal.\n");
}

$options = getopt('', ['banco::', 'uploads::']);
$dbPath = $options['banco'] ?? __DIR__ . '/database.sqlite';
$uploadsDir = rtrim($options['uploads'] ?? __DIR__ . '/uploads', DIRECTORY_SEPARATOR);

if (!is_file($dbPath)) {
    fwrite(STDERR, "Banco nao encontrado: {$dbPath}\n");
    exit(1);
}
if (!is_dir($uploadsDir)) {
    fwrite(STDERR, "Pasta de uploads nao encontrada: {$uploadsDir}\n");
    exit(1);
}

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$registered = [];
foreach ($db->query('SELECT id, nome, url FROM images ORDER BY id') as $image) {
    $url = trim((string)($image['url'] ?? ''));
    if (!str_starts_with($url, 'uploads/')) {
        $registered[(int)$image['id']] = [
            'id' => (int)$image['id'],
            'nome' => $image['nome'],
            'url' => $url,
            'arquivo' => null,
            'status' => 'url_invalida',
        ];
        continue;
    }

    $fileName = basename($url);
    $path = $uploadsDir . DIRECTORY_SEPARATOR . $fileName;
    $registered[(int)$image['id']] = [
        'id' => (int)$image['id'],
        'nome' => $image['nome'],
        'url' => $url,
        'arquivo' => $fileName,
        'status' => is_file($path) ? 'ok' : 'arquivo_ausente',
    ];
}

$physicalFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($uploadsDir, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getFilename() === '.htaccess') {
        continue;
    }
    $relative = str_replace(['/', '\\'], '/', substr($file->getPathname(), strlen($uploadsDir) + 1));
    $physicalFiles[$relative] = [
        'arquivo' => $relative,
        'tamanho' => $file->getSize(),
        'alterado_em' => date('c', $file->getMTime()),
        'status' => 'nao_cadastrado',
    ];
}

foreach ($registered as $image) {
    if ($image['arquivo'] !== null && isset($physicalFiles[$image['arquivo']])) {
        $physicalFiles[$image['arquivo']]['status'] = 'cadastrado';
    }
}

$missing = array_values(array_filter($registered, fn(array $image): bool => $image['status'] !== 'ok'));
$orphans = array_values(array_filter($physicalFiles, fn(array $file): bool => $file['status'] === 'nao_cadastrado'));
$summary = [
    'registros_no_banco' => count($registered),
    'arquivos_fisicos' => count($physicalFiles),
    'cadastrados_com_arquivo' => count($registered) - count($missing),
    'registros_sem_arquivo' => count($missing),
    'arquivos_nao_cadastrados' => count($orphans),
];

echo json_encode([
    'resumo' => $summary,
    'registros_sem_arquivo' => $missing,
    'arquivos_nao_cadastrados' => $orphans,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
