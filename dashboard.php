<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/Backup.php';
require_once 'includes/Logger.php';
requirePermission('dashboard_ver');

$db = getDB();

// ===== ESTATÍSTICAS =====

// Total de imagens
$totalImagens = $db->query("SELECT COUNT(*) FROM images")->fetchColumn();

// Total de álbuns
$totalAlbuns = $db->query("SELECT COUNT(*) FROM albums")->fetchColumn();

// Total de usuários
$totalUsuarios = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

// Espaço em disco usado
$uploads_dir = UPLOAD_DIR;
$totalSize = 0;
if (is_dir($uploads_dir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploads_dir),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($files as $file) {
        if ($file->isFile()) {
            $totalSize += $file->getSize();
        }
    }
}

// Imagens por álbum (top 5)
$stmt = $db->query("
    SELECT a.nome, COUNT(i.id) as total 
    FROM albums a 
    LEFT JOIN images i ON a.id = i.album_id 
    GROUP BY a.id 
    ORDER BY total DESC 
    LIMIT 5
");
$albumsTop = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Imagens sem álbum
$imagensSemAlbum = $db->query("SELECT COUNT(*) FROM images WHERE album_id IS NULL")->fetchColumn();

// Últimas imagens (últimas 10)
$ultimasImagens = $db->query("
    SELECT id, nome, data, tamanho 
    FROM images 
    ORDER BY id DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Últimos usuários criados (últimos 5)
$ultimosUsuarios = $db->query("
    SELECT id, usuario, nome, criado_em 
    FROM usuarios 
    ORDER BY id DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Backup
$backup = new Backup();
$statsBackup = $backup->getStats();

// Logger
$logger = new Logger();
$logsRecentes = array_slice($logger->getLogs(50), -10); // Últimos 10

// Estatísticas de atividade (últimos 7 dias)
$logFile = dirname(__DIR__) . '/logs/activity.log';
$atividade7dias = 0;
$cutoff = time() - (7 * 24 * 60 * 60);

if (file_exists($logFile)) {
    $lines = file($logFile);
    foreach ($lines as $line) {
        // Extrair data do log
        preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $line, $matches);
        if (!empty($matches)) {
            $logTime = strtotime("{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}:{$matches[5]}:{$matches[6]}");
            if ($logTime > $cutoff) {
                $atividade7dias++;
            }
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
    <title>Dashboard - Bograntex</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            border-left: 4px solid #EE5A52;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 32px;
            color: #EE5A52;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-card.primary { border-left-color: #007bff; }
        .stat-card.primary .stat-icon { color: #007bff; }
        
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.success .stat-icon { color: #28a745; }
        
        .stat-card.info { border-left-color: #17a2b8; }
        .stat-card.info .stat-icon { color: #17a2b8; }
        
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.warning .stat-icon { color: #ffc107; }
        
        .dashboard-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
            border-bottom: 2px solid #EE5A52;
            padding-bottom: 15px;
        }
        
        .chart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .chart-item:last-child {
            border-bottom: none;
        }
        
        .chart-label {
            flex: 1;
            font-weight: 500;
            color: #333;
        }
        
        .chart-bar {
            flex: 2;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 0 15px;
        }
        
        .chart-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #EE5A52, #FF6B5B);
            transition: width 0.3s;
        }
        
        .chart-value {
            font-weight: bold;
            color: #666;
            min-width: 50px;
            text-align: right;
        }
        
        .table-simple {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-simple thead {
            background: #f9f9f9;
        }
        
        .table-simple th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .table-simple td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }
        
        .table-simple tr:hover {
            background: #f9f9f9;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        @media (max-width: 768px) {
            .grid-2col {
                grid-template-columns: 1fr;
            }
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
            <h2><i class="fas fa-chart-bar"></i> Dashboard</h2>
            <p style="color: #666; margin-top: 10px;">Visão geral do sistema</p>
        </div>

        <nav class="menu">
            <a href="index.php"><i class="fas fa-th"></i> Todas as imagens</a>
            <a href="albums.php"><i class="fas fa-folder"></i> Álbuns</a>
            <a href="usuarios.php"><i class="fas fa-users"></i> Usuários</a>
            <a href="logs.php"><i class="fas fa-history"></i> Logs</a>
            <a href="backups.php"><i class="fas fa-database"></i> Backups</a>
            <a href="dashboard.php" class="active"><i class="fas fa-chart-bar"></i> Dashboard</a>
        </nav>

        <!-- ESTATÍSTICAS PRINCIPAIS -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-image"></i></div>
                <div class="stat-label">Total de Imagens</div>
                <div class="stat-value"><?php echo number_format($totalImagens); ?></div>
            </div>
            
            <div class="stat-card primary">
                <div class="stat-icon"><i class="fas fa-folder"></i></div>
                <div class="stat-label">Total de Álbuns</div>
                <div class="stat-value"><?php echo number_format($totalAlbuns); ?></div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-label">Usuários Cadastrados</div>
                <div class="stat-value"><?php echo number_format($totalUsuarios); ?></div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-hdd"></i></div>
                <div class="stat-label">Espaço em Uso</div>
                <div class="stat-value" style="font-size: 20px;"><?php echo formatSize($totalSize); ?></div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-box"></i></div>
                <div class="stat-label">Imagens sem Álbum</div>
                <div class="stat-value"><?php echo number_format($imagensSemAlbum); ?></div>
                <a href="index.php?sem_album=1" class="btn-small" style="display:inline-flex; margin-top:12px; align-items:center; gap:6px; text-decoration:none;">
                    <i class="fas fa-eye"></i> Visualizar
                </a>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-history"></i></div>
                <div class="stat-label">Ações (Últimos 7 dias)</div>
                <div class="stat-value"><?php echo number_format($atividade7dias); ?></div>
            </div>
        </div>

        <!-- SEÇÃO 2 COLUNAS -->
        <div class="grid-2col">
            <!-- ÁLBUNS TOP -->
            <div class="dashboard-section">
                <div class="section-title">
                    <i class="fas fa-star"></i> Álbuns com Mais Imagens
                </div>
                
                <?php if (empty($albumsTop)): ?>
                    <p style="color: #999; text-align: center; padding: 20px;">Nenhum álbum com imagens</p>
                <?php else: ?>
                    <?php 
                    $maxTotal = max(array_column($albumsTop, 'total')) ?: 1;
                    foreach ($albumsTop as $album):
                        $percentage = ($album['total'] / $maxTotal) * 100;
                    ?>
                        <div class="chart-item">
                            <div class="chart-label"><?php echo htmlspecialchars($album['nome']); ?></div>
                            <div class="chart-bar">
                                <div class="chart-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <div class="chart-value"><?php echo $album['total']; ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- BACKUPS -->
            <div class="dashboard-section">
                <div class="section-title">
                    <i class="fas fa-database"></i> Backups
                </div>
                
                <div class="chart-item" style="border: none;">
                    <div class="chart-label">Total de Backups</div>
                    <div class="chart-value"><?php echo $statsBackup['count']; ?></div>
                </div>
                
                <div class="chart-item" style="border: none;">
                    <div class="chart-label">Espaço Utilizado</div>
                    <div class="chart-value"><?php echo $statsBackup['total_size_formatted']; ?></div>
                </div>
                
                <div class="chart-item" style="border: none;">
                    <div class="chart-label">Mais Recente</div>
                    <div class="chart-value" style="font-size: 12px; text-align: right;"><?php echo $statsBackup['newest']; ?></div>
                </div>
                
                <div style="margin-top: 20px; text-align: center;">
                    <a href="backups.php" class="btn-primary" style="display: inline-block;">
                        <i class="fas fa-database"></i> Gerenciar Backups
                    </a>
                </div>
            </div>
        </div>

        <!-- ÚLTIMAS IMAGENS -->
        <div class="dashboard-section">
            <div class="section-title">
                <i class="fas fa-clock"></i> Últimas Imagens Enviadas
            </div>
            
            <?php if (empty($ultimasImagens)): ?>
                <p style="color: #999; text-align: center; padding: 20px;">Nenhuma imagem enviada</p>
            <?php else: ?>
                <table class="table-simple">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Data</th>
                            <th>Tamanho</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimasImagens as $img): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars(substr($img['nome'], 0, 50)); ?></strong></td>
                                <td><span class="badge badge-info"><?php echo $img['data']; ?></span></td>
                                <td><?php echo $img['tamanho']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- ÚLTIMOS USUÁRIOS -->
        <div class="dashboard-section">
            <div class="section-title">
                <i class="fas fa-user-plus"></i> Últimos Usuários Criados
            </div>
            
            <?php if (empty($ultimosUsuarios)): ?>
                <p style="color: #999; text-align: center; padding: 20px;">Nenhum usuário criado</p>
            <?php else: ?>
                <table class="table-simple">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Nome</th>
                            <th>Data de Criação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimosUsuarios as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['usuario']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['nome']); ?></td>
                                <td><span class="badge badge-success"><?php echo $user['criado_em']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- LOGS RECENTES -->
        <div class="dashboard-section">
            <div class="section-title">
                <i class="fas fa-list"></i> Últimas Ações
            </div>
            
            <?php if (empty($logsRecentes)): ?>
                <p style="color: #999; text-align: center; padding: 20px;">Nenhuma ação registrada</p>
            <?php else: ?>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach (array_reverse($logsRecentes) as $log): ?>
                        <div style="padding: 10px; border-bottom: 1px solid #f0f0f0; font-size: 12px; font-family: monospace;">
                            <span style="color: #999;"><?php echo htmlspecialchars($log); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- AVISO DE SESSÃO EXPIRANDO -->
    <?php if (isLoggedIn()): ?>
        <?php require 'includes/partials/session_warning.php'; ?>
    <?php endif; ?>

    <script src="assets/js/app.js"></script>
</body>
</html>