<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/Logger.php';
requirePermission('logs_ver');

$logger = new Logger();
$logs = $logger->getLogs(500); // Últimos 500 registros
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs - Bograntex</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .logs-container {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .log-entry {
            background: white;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 4px solid #007bff;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .log-entry.image_uploaded { border-left-color: #28a745; }
        .log-entry.image_deleted { border-left-color: #dc3545; }
        .log-entry.album_created { border-left-color: #17a2b8; }
        .log-entry.album_deleted { border-left-color: #ffc107; }
        .log-entry.user_created { border-left-color: #6f42c1; }
        .log-entry.user_deleted { border-left-color: #e83e8c; }
        
        .log-time { color: #666; font-weight: bold; }
        .log-user { color: #007bff; }
        .log-action { color: #dc3545; font-weight: bold; }
        .log-details { color: #333; }
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
            <h2><i class="fas fa-history"></i> Logs de Atividade</h2>
            <p style="color: #666; margin-top: 10px;">Últimos 500 registros</p>
        </div>

        <nav class="menu">
            <a href="index.php"><i class="fas fa-th"></i> Todas as imagens</a>
            <a href="albums.php"><i class="fas fa-folder"></i> Álbuns</a>
            <a href="usuarios.php"><i class="fas fa-users"></i> Usuários</a>
            <a href="logs.php" class="active"><i class="fas fa-history"></i> Logs</a>
            <a href="backups.php"><i class="fas fa-database"></i> Backups</a>
            <a href="dashboard.php"><i class="fas fa-chart-bar"></i> Dashboard</a> 
        </nav>

        <div class="logs-container">
            <?php if (empty($logs)): ?>
                <p style="text-align: center; color: #999;">Nenhum registro de atividade</p>
            <?php else: ?>
                <?php foreach (array_reverse($logs) as $log): ?>
                    <?php
                    // Extrai a ação do log para colorir
                    preg_match('/\| Action: (\w+)/', $log, $matches);
                    $action = $matches[1] ?? 'unknown';
                    $actionClass = htmlspecialchars($action, ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="log-entry <?php echo $actionClass; ?>">
                        <?php echo htmlspecialchars($log); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <!-- AVISO DE SESSÃO EXPIRANDO -->
    <?php if (isLoggedIn()): ?>
        <?php require 'includes/partials/session_warning.php'; ?>
    <?php endif; ?>
</body>
</html>