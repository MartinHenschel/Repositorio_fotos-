<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/Backup.php';
require_once 'includes/csrf.php';
requireLogin();

// Para ver a página é preciso ter pelo menos uma das permissões de backup.
// As ações específicas (criar / restaurar) são checadas individualmente abaixo.
if (!usuarioTemPermissao('backups_criar') && !usuarioTemPermissao('backups_restaurar')) {
    requirePermission('backups_criar'); // dispara a tela de acesso negado
}

$backup = new Backup();
$backups = $backup->listBackups();
$stats = $backup->getStats();
$message = '';
$error = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrfOuFalhar();

    if (isset($_POST['action']) && isset($_POST['filename'])) {
        $filename = basename($_POST['filename']); // Sanitizar
        
        if ($_POST['action'] === 'restore') {
            requirePermission('backups_restaurar');
            $confirmacao = trim((string)($_POST['confirmacao_restauracao'] ?? ''));
            if ($confirmacao !== 'RESTAURAR') {
                $error = 'Restauração cancelada: digite RESTAURAR para confirmar.';
            } else {
                $usuarioAtual = currentUser();
                $logger = new Logger();
                $logger->log('backup_restore_requested', $usuarioAtual['id'], "Arquivo: {$filename}");
                $result = $backup->restoreBackup($filename);
                if ($result['success']) {
                    $message = $result['message'];
                    $logger->log('backup_restore_succeeded', $usuarioAtual['id'], "Arquivo: {$filename}");
                    $backups = $backup->listBackups();
                } else {
                    $error = $result['error'];
                    $logger->log('backup_restore_failed', $usuarioAtual['id'], "Arquivo: {$filename}");
                }
            }
        }
        
        if ($_POST['action'] === 'download') {
            requirePermission('backups_restaurar'); // ver observação abaixo sobre criar uma permissão dedicada
            $backupInfo = $backup->getBackupInfo($filename);
            if ($backupInfo) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . $backupInfo['size']);
                readfile($backupInfo['path']);
                exit;
            }
        }
    }
    


    if (isset($_POST['action']) && $_POST['action'] === 'backup_now') {
        requirePermission('backups_criar');
        $result = $backup->backupDatabase();
        if ($result['success']) {
            $message = 'Backup criado com sucesso! (' . round($result['size'] / 1024, 1) . ' KB)';
            $backups = $backup->listBackups();
        } else {
            $error = $result['error'];
        }
    }
}

$usuarioLogado = currentUser();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backups - Bograntex</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .backup-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #EE5A52;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .backup-list {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .backup-item:last-child {
            border-bottom: none;
        }
        
        .backup-info {
            flex: 1;
        }
        
        .backup-date {
            font-weight: bold;
            color: #333;
            margin-bottom: 4px;
        }
        
        .backup-size {
            font-size: 12px;
            color: #999;
        }
        
        .backup-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s;
        }
        
        .btn-restore {
            background: #28a745;
            color: white;
        }
        
        .btn-restore:hover {
            background: #218838;
        }
        
        .btn-download {
            background: #007bff;
            color: white;
        }
        
        .btn-download:hover {
            background: #0056b3;
        }
        
        .message-success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .message-error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="logo">Bogran<span>Tex</span></div>
        <div class="topbar-actions">
            <a href="upload.php" class="btn-primary"><i class="fas fa-upload"></i> Enviar</a>
            <?php echo getLogoutButton(); ?>
        </div>
    </header>

    <div class="container">
        <div class="user-header">
            <h2><i class="fas fa-database"></i> Backups</h2>
            <p style="color: #666; margin-top: 10px;">Gerenciar backups do banco de dados</p>
        </div>

        <nav class="menu">
            <a href="index.php"><i class="fas fa-th"></i> Todas as imagens</a>
            <a href="albums.php"><i class="fas fa-folder"></i> Álbuns</a>
            <a href="usuarios.php"><i class="fas fa-users"></i> Usuários</a>
            <a href="logs.php"><i class="fas fa-history"></i> Logs</a>
            <a href="backups.php" class="active"><i class="fas fa-database"></i> Backups</a>
            <a href="dashboard.php"><i class="fas fa-chart-bar"></i> Dashboard</a> 
        </nav>

        <!-- Mensagens -->
        <?php if (!empty($message)): ?>
            <div class="message-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="message-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Botão para forçar backup agora -->
        <form method="POST" style="margin-bottom: 20px;">
            <?php echo csrfField(); ?>
            <button type="submit" name="action" value="backup_now" class="btn-primary">
                <i class="fas fa-plus"></i> Fazer Backup Agora
            </button>
        </form>

        <!-- Estatísticas -->
        <div class="backup-stats">
            <div class="stat-card">
                <div class="stat-label"><i class="fas fa-database"></i> Total de Backups</div>
                <div class="stat-value"><?php echo $stats['count']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="fas fa-hdd"></i> Espaço Total</div>
                <div class="stat-value"><?php echo $stats['total_size_formatted']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="fas fa-clock"></i> Mais Recente</div>
                <div class="stat-value" style="font-size: 14px;"><?php echo $stats['newest']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="fas fa-history"></i> Mais Antigo</div>
                <div class="stat-value" style="font-size: 14px;"><?php echo $stats['oldest']; ?></div>
            </div>
        </div>

        <!-- Lista de Backups -->
        <h3 style="margin-bottom: 15px;">Backups Disponíveis</h3>
        
        <?php if (empty($backups)): ?>
            <div style="background: #f9f9f9; padding: 40px; text-align: center; border-radius: 8px; color: #999;">
                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px; opacity: 0.5;"></i>
                <p>Nenhum backup encontrado</p>
            </div>
        <?php else: ?>
            <div class="backup-list">
                <?php foreach ($backups as $bak): ?>
                    <div class="backup-item">
                        <div class="backup-info">
                            <div class="backup-date">
                                <i class="fas fa-calendar"></i> <?php echo $bak['date']; ?>
                            </div>
                            <div class="backup-size">
                                Tamanho: <?php echo round($bak['size'] / 1024, 1); ?> KB
                            </div>
                        </div>
                        <div class="backup-actions">
                            <form method="POST" style="display: inline;">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="download">
                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($bak['filename']); ?>">
                                <button type="submit" class="btn-small btn-download">
                                    <i class="fas fa-download"></i> Baixar
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirmarRestauracao(this);">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="restore">
                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($bak['filename']); ?>">
                                <input type="hidden" name="confirmacao_restauracao" value="">
                                <button type="submit" class="btn-small btn-restore">
                                    <i class="fas fa-redo"></i> Restaurar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- AVISO DE SESSÃO EXPIRANDO -->
    <?php if (isLoggedIn()): ?>
        <?php require 'includes/partials/session_warning.php'; ?>
    <?php endif; ?>

    <script src="assets/js/app.js"></script>
    <script>
        function confirmarRestauracao(formulario) {
            const confirmacao = prompt(
                'A restauração substituirá os dados atuais.\n' +
                'Digite RESTAURAR para confirmar:'
            );
            if (confirmacao !== 'RESTAURAR') {
                return false;
            }
            formulario.elements.confirmacao_restauracao.value = confirmacao;
            return true;
        }
    </script>
</body>
</html>