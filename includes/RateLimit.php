<?php
class RateLimit {
    private $file;
    private $maxAttempts = 5;
    private $windowMinutes = 15;

    public function __construct($storagePath = null) {
        if ($storagePath === null) {
            $storagePath = dirname(__DIR__) . '/storage';
        }
        $this->file = $storagePath . '/login_attempts.json';
        
        // Criar diretório se não existir
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
    }

    /**
     * Verifica se o IP pode tentar fazer login
     */
    public function isAllowed($ip) {
        $attempts = $this->getAttempts();
        $now = time();
        $windowStart = $now - ($this->windowMinutes * 60);

        // Remove tentativas antigas
        $attempts = array_filter($attempts, function($attempt) use ($windowStart) {
            return $attempt['time'] > $windowStart;
        });

        // Conta tentativas do IP
        $ipAttempts = array_filter($attempts, function($attempt) use ($ip) {
            return $attempt['ip'] === $ip;
        });

        return count($ipAttempts) < $this->maxAttempts;
    }

    /**
     * Registra uma tentativa de login falhada
     */
    public function recordAttempt($ip) {
        $now = time();
        $windowStart = $now - ($this->windowMinutes * 60);

        $this->updateAttempts(function (array $attempts) use ($ip, $now, $windowStart): array {
            $attempts = array_filter($attempts, function ($attempt) use ($windowStart) {
                return (int)$attempt['time'] > $windowStart;
            });
            $attempts[] = ['ip' => $ip, 'time' => $now];
            return $attempts;
        });
    }

    /**
     * Retorna o tempo restante (em segundos) até poder tentar novamente
     */
    public function getTimeRemaining($ip) {
        $attempts = $this->getAttempts();
        $now = time();
        $windowStart = $now - ($this->windowMinutes * 60);

        $ipAttempts = array_filter($attempts, function($attempt) use ($ip, $windowStart) {
            return $attempt['ip'] === $ip && $attempt['time'] > $windowStart;
        });

        if (count($ipAttempts) < $this->maxAttempts) {
            return 0;
        }

        $oldestAttempt = min(array_map(function($a) { return $a['time']; }, $ipAttempts));
        $timeRemaining = ($oldestAttempt + ($this->windowMinutes * 60)) - $now;

        return max(0, $timeRemaining);
    }

    /**
     * Limpa as tentativas do IP (usado após login bem-sucedido)
     */
    public function clearAttempts($ip) {
        $this->updateAttempts(function (array $attempts) use ($ip): array {
            return array_filter($attempts, function ($attempt) use ($ip) {
                return $attempt['ip'] !== $ip;
            });
        });
    }

    /**
     * Retorna o número de tentativas do IP
     */
    public function getAttemptCount($ip) {
        $attempts = $this->getAttempts();
        $now = time();
        $windowStart = $now - ($this->windowMinutes * 60);

        $ipAttempts = array_filter($attempts, function($attempt) use ($ip, $windowStart) {
            return $attempt['ip'] === $ip && $attempt['time'] > $windowStart;
        });

        return count($ipAttempts);
    }

    // ===== Métodos Privados =====

    private function getAttempts() {
        if (!file_exists($this->file)) {
            return [];
        }

        $content = file_get_contents($this->file);
        $attempts = json_decode($content, true);

        if (!is_array($attempts)) {
            return [];
        }

        return array_values(array_filter($attempts, function ($attempt) {
            return is_array($attempt)
                && isset($attempt['ip'], $attempt['time'])
                && is_string($attempt['ip'])
                && is_numeric($attempt['time']);
        }));
    }

    private function saveAttempts($attempts) {
        $temporaryFile = $this->file . '.tmp';
        $json = json_encode(array_values($attempts), JSON_THROW_ON_ERROR);
        if (file_put_contents($temporaryFile, $json, LOCK_EX) === false || !rename($temporaryFile, $this->file)) {
            @unlink($temporaryFile);
            error_log('Não foi possível atualizar o rate limit de login.');
        }
    }

    private function updateAttempts(callable $update): void
    {
        $lockFile = $this->file . '.lock';
        $handle = fopen($lockFile, 'c');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            error_log('Não foi possível bloquear o rate limit de login.');
            return;
        }

        try {
            $this->saveAttempts($update($this->getAttempts()));
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
?>