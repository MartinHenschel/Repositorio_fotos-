<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
requirePermission('albuns_ver');

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');

$albumId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($albumId <= 0) {
    http_response_code(400);
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head><meta charset="UTF-8"><title>Álbum inválido</title><link rel="stylesheet" href="assets/css/style.css"></head>
    <body><div class="container"><p>Álbum inválido.</p><p><a href="albums.php">← Voltar para álbuns</a></p></div></body>
    </html>
    <?php
    exit;
}

$album = getAlbum($albumId);
if (!$album) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head><meta charset="UTF-8"><title>Álbum não encontrado</title><link rel="stylesheet" href="assets/css/style.css"></head>
    <body><div class="container"><p>Álbum não encontrado.</p><p><a href="albums.php">← Voltar para álbuns</a></p></div></body>
    </html>
    <?php
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$paginacao = getImagesByAlbumPaginated($albumId, $page, 15);
$imagens = $paginacao['images'];
$urlBase = "album.php?id={$albumId}";

$totalImagens = countImages();
$totalAlbuns = countAlbums();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrfToken()); ?>">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <title>BogranTex - <?php echo htmlspecialchars($album['nome']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
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
            <h2 id="album-titulo"><?php echo htmlspecialchars($album['nome']); ?></h2>
            <div class="stats">
                <span><i class="fas fa-image"></i> <?php echo $totalImagens; ?> imagens</span>
                <span><i class="fas fa-folder"></i> <?php echo $totalAlbuns; ?> álbuns</span>
            </div>
        </div>

        <nav class="menu">
            <a href="index.php"><i class="fas fa-th"></i> Todas as imagens</a>
            <a href="albums.php"><i class="fas fa-folder"></i> Álbuns</a>
            <a href="usuarios.php"><i class="fas fa-users"></i> Usuários</a>
            <a href="logs.php"><i class="fas fa-history"></i> Logs</a>
            <a href="backups.php"><i class="fas fa-database"></i> Backups</a>
            <a href="dashboard.php"><i class="fas fa-chart-bar"></i> Dashboard</a>
        </nav>

        <div class="album-actions-top">
    <a href="download_album.php?id=<?php echo $albumId; ?>" class="btn-danger">
        <i class="fas fa-file-excel"></i> Gerar URLs (.xlsx)    
    </a>
</div>
            <!--a href="download_album_csv.php?id=<?php echo $albumId; ?>" class="btn-secondary">
                <i class="fas fa-file-csv"></i> Baixar álbum (.csv)
            </a>
        </div>-->

        <?php require 'includes/partials/image_grid.php'; ?>
        <?php require 'includes/partials/pagination.php'; ?>
    </div>

    <?php require 'includes/partials/image_modal.php'; ?>
    <script src="assets/js/app.js"></script>
    <?php if (isLoggedIn()): ?>
        <?php require 'includes/partials/session_warning.php'; ?>
    <?php endif; ?>
</body>
</html>