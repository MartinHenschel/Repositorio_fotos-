<?php
// ===== AMBIENTE =====
// Nunca exiba erros do PHP diretamente pro usuário em produção . Os erros continuam sendo logados.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Configuração do banco de dados SQLite
define('DB_PATH', dirname(__DIR__) . '/database.sqlite');
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('MAX_IMAGE_WIDTH', 3286);
define('MAX_IMAGE_HEIGHT', 1080);
define('MAX_IMAGE_PIXELS', 3548880);

// Endereço público usado pela API para montar URLs das imagens.
$baseUrl = getenv('BASE_URL') ?: 'http://localhost/hospedagem_foto';
define('BASE_URL', rtrim($baseUrl, '/') . '/');

define('STORAGE_DIR', dirname(__DIR__) . '/storage/');
define('LOG_DIR', dirname(__DIR__) . '/logs/');
define('BACKUP_DIR', rtrim((getenv('BACKUP_DIR') ?: dirname(__DIR__, 3) . '/hospedagem_foto_backups'), '/\\') . DIRECTORY_SEPARATOR);

// ===== ATIVAR COMPRESSÃO GZIP =====
// Reduz o tamanho das respostas em até 80%
if (extension_loaded('zlib')) {
    ob_start('ob_gzhandler');
}

// ===== HEADERS DE CACHE =====
// Padrão do sistema: NÃO cachear (a maioria das páginas mostra dados de
// usuário logado). Páginas que quiserem cache público de verdade (ex: um
// asset estático) devem sobrescrever este header explicitamente.
if (!headers_sent()) {
    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');
}

// Criar pastas se não existirem
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
if (!is_dir(STORAGE_DIR)) {
    mkdir(STORAGE_DIR, 0755, true);
}
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}
ini_set('error_log', LOG_DIR . 'php_errors.log');

// ===== BACKUP AUTOMÁTICO =====
require_once 'Backup.php';

$backup = new Backup();

// Verificar se precisa fazer backup (a cada 24 horas)
$lastBackupFile = dirname(__DIR__) . '/storage/last_backup.txt';
$lastBackup = file_exists($lastBackupFile) ? (int)file_get_contents($lastBackupFile) : 0;
$now = time();

// Se passou 24 horas OU é a primeira vez
if ($now - $lastBackup > 86400) { // 86400 = 24 horas em segundos
    
    // Fazer backup
    $result = $backup->backupDatabase();
    
    if ($result['success']) {
        // Salvar timestamp do último backup
        file_put_contents($lastBackupFile, $now, LOCK_EX);
        
        // Limpar backups antigos (manter 30 dias)
        $backup->cleanOldBackups(30);
    }
}

?>