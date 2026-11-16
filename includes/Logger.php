<?php
class Logger {
    private $logFile;

    public function __construct($filename = 'activity.log') {
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $this->logFile = $logDir . '/' . $filename;
    }

    /**
     * Registra uma ação no log
     */
    public function log($action, $userId, $details = null) {
        $timestamp = date('Y-m-d H:i:s');
        $user = getUserById($userId);
        $userName = $user ? $user['usuario'] : 'unknown';
        
        $details = str_replace(["\r", "\n"], ' ', $details ?? '');
        $logEntry = "{$timestamp} | User: {$userId} ({$userName}) | Action: {$action}";
        
        if ($details) {
            $logEntry .= " | Details: {$details}";
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $logEntry .= " | IP: {$ip}\n";
        
        if (file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX) === false) {
            error_log('Não foi possível gravar o log de atividade.');
        }
    }

    /**
     * Retorna os últimos N logs
     */
    public function getLogs($limit = 100) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $lines = file($this->logFile);
        return array_slice($lines, -$limit);
    }
}
?>