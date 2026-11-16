<?php
require_once 'db.php';  

class Backup {
    private $backupDir;
    private $db;

    public function __construct() {
        $this->backupDir = BACKUP_DIR;
        $this->db = Database::getInstance()->getPDO();
        
        // Criar pasta se não existir
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Faz backup do banco de dados SQLite
     * Retorna array com resultado
     */
    public function backupDatabase() {
        try {
            $source = DB_PATH;
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $this->backupDir . '/database_' . $timestamp . '.sqlite';
            
            // Copiar arquivo
            if (copy($source, $backupFile)) {
                chmod($backupFile, 0644);
                
                $fileSize = filesize($backupFile);
                
                // Log do backup
                $this->logBackup('backup_created', $timestamp, $fileSize);
                
                return [
                    'success' => true,
                    'file' => $backupFile,
                    'filename' => basename($backupFile),
                    'size' => $fileSize,
                    'date' => $timestamp
                ];
            } else {
                return ['success' => false, 'error' => 'Erro ao copiar arquivo'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Remove backups mais antigos que X dias
     */
    public function cleanOldBackups($days = 7) {
        try {
            $files = glob($this->backupDir . '/database_*.sqlite');
            $cutoff = time() - ($days * 24 * 60 * 60);
            $deletedCount = 0;
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff) {
                    if (unlink($file)) {
                        $deletedCount++;
                        $this->logBackup('backup_deleted', basename($file), filesize($file));
                    }
                }
            }
            
            return ['success' => true, 'deleted' => $deletedCount];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Lista todos os backups disponíveis
     */
    public function listBackups() {
        try {
            $files = glob($this->backupDir . '/database_*.sqlite');
            $backups = [];
            
            foreach ($files as $file) {
                $filename = basename($file);
                
                // Extrair data do nome: database_2025-07-27_14-30-00.sqlite
                preg_match('/database_(.+)\.sqlite/', $filename, $matches);
                $dateStr = $matches[1] ?? '';
                
                // Converter para formato legível: 2025-07-27 14:30:00
                $dateFormatted = str_replace('_', ' ', $dateStr);
                
                $backups[] = [
                    'filename' => $filename,
                    'path' => $file,
                    'date' => $dateFormatted,
                    'size' => filesize($file),
                    'timestamp' => filemtime($file)
                ];
            }
            
            // Ordenar por data decrescente (mais recentes primeiro)
            usort($backups, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
            
            return $backups;
            
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Retorna informações sobre um backup específico
     */
    public function getBackupInfo($filename) {
        $filePath = $this->backupDir . '/' . basename($filename);
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        return [
            'filename' => basename($filePath),
            'path' => $filePath,
            'size' => filesize($filePath),
            'date' => date('d/m/Y H:i:s', filemtime($filePath)),
            'timestamp' => filemtime($filePath)
        ];
    }

    /**
     * Restaura um backup (cria backup da versão atual antes)
     */
    public function restoreBackup($backupFilename) {
        $transactionStarted = false;
        try {
            $backupPath = $this->backupDir . '/' . basename($backupFilename);
            
            // Validar que arquivo existe
            if (!file_exists($backupPath)) {
                return ['success' => false, 'error' => 'Arquivo de backup não encontrado'];
            }

            $sourceDb = new PDO('sqlite:' . $backupPath);
            $sourceDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            if ($sourceDb->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
                return ['success' => false, 'error' => 'O backup está corrompido'];
            }
            
            // Fazer backup do DB atual (segurança)
            $this->backupDatabase();

            $this->db->beginTransaction();
            $transactionStarted = true;
            $this->db->exec('DELETE FROM images');
            $this->db->exec('DELETE FROM albums');
            $this->db->exec('DELETE FROM usuarios');

            $this->copyTableFromBackup($sourceDb, 'albums', ['id', 'nome', 'capa']);
            $this->copyTableFromBackup($sourceDb, 'images', ['id', 'nome', 'tamanho', 'data', 'url', 'album_id']);
            $this->copyTableFromBackup($sourceDb, 'usuarios', ['id', 'usuario', 'senha', 'nome', 'criado_em', 'cargo', 'permissoes']);
            $this->db->commit();
            $transactionStarted = false;

            $this->logBackup('backup_restored', $backupFilename, filesize($backupPath));
            return ['success' => true, 'message' => 'Backup restaurado com sucesso'];
        } catch (Throwable $e) {
            if ($transactionStarted && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Falha ao restaurar backup: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao restaurar backup'];
        }
    }

    private function copyTableFromBackup(PDO $sourceDb, string $table, array $columns): void
    {
        $availableColumns = array_column(
            $sourceDb->query("PRAGMA table_info({$table})")->fetchAll(PDO::FETCH_ASSOC),
            'name'
        );
        $columns = array_values(array_intersect($columns, $availableColumns));
        if (!$columns) {
            return;
        }

        $columnList = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $insert = $this->db->prepare("INSERT INTO {$table} ({$columnList}) VALUES ({$placeholders})");
        $rows = $sourceDb->query("SELECT {$columnList} FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $insert->execute(array_values($row));
        }
    }

    /**
     * Obtém estatísticas dos backups
     */
    public function getStats() {
        $backups = $this->listBackups();
        $totalSize = 0;
        
        foreach ($backups as $backup) {
            $totalSize += $backup['size'];
        }
        
        return [
            'count' => count($backups),
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatSize($totalSize),
            'oldest' => !empty($backups) ? end($backups)['date'] : 'N/A',
            'newest' => !empty($backups) ? $backups[0]['date'] : 'N/A'
        ];
    }

    /**
     * Registra evento de backup no log
     */
    private function logBackup($action, $details, $size) {
        try {
            $logFile = dirname(__DIR__) . '/logs/backup.log';
            $timestamp = date('Y-m-d H:i:s');
            $sizeFormatted = $this->formatSize($size);
            
            $logEntry = "{$timestamp} | Action: {$action} | Details: {$details} | Size: {$sizeFormatted}\n";
            
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Silenciosamente falha se não conseguir logar
        }
    }

    /**
     * Formata tamanho em bytes
     */
    private function formatSize($bytes) {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
?>