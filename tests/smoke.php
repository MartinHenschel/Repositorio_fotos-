<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Execute este teste pelo terminal.\n");
}

$root = dirname(__DIR__);
$failures = [];
$passed = 0;

function check(bool $condition, string $description): void
{
    global $failures, $passed;
    if ($condition) {
        $passed++;
        echo "PASS: {$description}\n";
    } else {
        $failures[] = $description;
        echo "FAIL: {$description}\n";
    }
}

$dbPath = $root . '/database.sqlite';
check(is_file($dbPath), 'banco SQLite existe');

if (is_file($dbPath)) {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    check($db->query('PRAGMA integrity_check')->fetchColumn() === 'ok', 'banco SQLite está íntegro');

    $tables = $db->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(PDO::FETCH_COLUMN);
    foreach (['albums', 'images', 'usuarios'] as $table) {
        check(in_array($table, $tables, true), "tabela {$table} existe");
    }

    $missing = (int)$db->query("SELECT COUNT(*) FROM images WHERE url NOT LIKE 'uploads/%'")->fetchColumn();
    check($missing === 0, 'todas as imagens usam caminhos locais válidos');

    $admin = $db->query("SELECT senha, cargo FROM usuarios WHERE usuario = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    check($admin !== false, 'usuário administrador existe');
    if ($admin !== false) {
        check(password_get_info($admin['senha'])['algo'] !== 0, 'senha do administrador está com hash');
        check($admin['cargo'] === 'administrador', 'administrador mantém o cargo correto');
    }
}

$requiredFiles = [
    '.htaccess',
    'uploads/.htaccess',
    'includes/csrf.php',
    'api_upload.php',
    'excluir.php',
    'thumbnail.php',
];
foreach ($requiredFiles as $file) {
    check(is_file($root . '/' . $file), "arquivo de proteção {$file} existe");
}

$rootHtaccess = file_get_contents($root . '/.htaccess');
$uploadsHtaccess = file_get_contents($root . '/uploads/.htaccess');
$apiUpload = file_get_contents($root . '/api_upload.php');
$delete = file_get_contents($root . '/excluir.php');
$thumbnail = file_get_contents($root . '/thumbnail.php');

check(str_contains($rootHtaccess, 'Require all denied'), 'arquivos sensíveis estão bloqueados no Apache');
check(str_contains($uploadsHtaccess, 'Require all denied'), 'scripts estão bloqueados em uploads');
check(str_contains($apiUpload, 'is_uploaded_file'), 'API valida upload real');
check(str_contains($apiUpload, 'finfo(FILEINFO_MIME_TYPE)'), 'API valida MIME do upload');
check(str_contains($apiUpload, 'MAX_IMAGE_WIDTH'), 'API valida dimensões da imagem');
check(!str_contains($apiUpload, 'ORDER BY id DESC LIMIT 1'), 'API não busca a última imagem global');
check(str_contains($apiUpload, 'exigirCsrfValido'), 'API exige CSRF');
check(str_contains($apiUpload, "imagens_upload"), 'API exige permissão de upload');
check(str_contains($delete, 'verificarCsrfOuFalhar'), 'exclusão exige CSRF');
check(str_contains($delete, "imagens_excluir"), 'exclusão exige permissão');
check(str_contains($thumbnail, "requirePermission('imagens_ver')"), 'miniaturas exigem permissão');

if ($failures !== []) {
    echo "\nFalhas: " . count($failures) . "\n";
    exit(1);
}

echo "\nTudo certo: {$passed} verificações passaram.\n";
