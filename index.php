<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
requirePermission('imagens_ver');
// ===== CACHE =====
// Não faz requisição novamente por 1 hora se nada mudou
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');

// Pegar página do URL (padrão: 1)
$page = (int)($_GET['page'] ?? 1);

// Buscar imagens paginadas, com filtro opcional para imagens sem álbum
$somenteSemAlbum = isset($_GET['sem_album']) && $_GET['sem_album'] === '1';
$paginacao = $somenteSemAlbum
    ? getImagesWithoutAlbumPaginated($page, 15)
    : getImagesPaginated($page, 15);
$imagens = $paginacao['images'];
$totalImagens = $paginacao['total'];
$totalAlbuns = countAlbums();
$usuarioLogado = currentUser();
$urlBase = $somenteSemAlbum ? 'index.php?sem_album=1' : 'index.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrfToken()); ?>">
    <title>Bograntex - Hospedagem de imagens</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- TOP BAR (fixa, estilo Bograntex) -->
    <header class="topbar">
    <div class="logo">Bogran<span>Tex</span></div>
    <div class="topbar-actions">
        <a href="upload.php" class="btn-primary"><i class="fas fa-upload"></i> Enviar</a>
        <?php echo getLogoutButton(); ?>
    </div>
</header>

    <div class="container">
        <!-- USER HEADER -->
        <div class="user-header">
            <h2><?php echo $somenteSemAlbum ? 'Imagens sem Álbum' : 'Todas as Fotos'; ?></h2>
            <div class="stats">
                <span><i class="fas fa-image"></i> <?php echo $totalImagens; ?> imagens</span>
                <span><i class="fas fa-folder"></i> <?php echo $totalAlbuns; ?> álbuns</span>
            </div>
        </div>

        <!-- MENU -->
        <nav class="menu">
    <a href="index.php" class="active"><i class="fas fa-th"></i> Todas as imagens</a>
    <a href="albums.php"><i class="fas fa-folder"></i> Álbuns</a>
    <a href="usuarios.php"><i class="fas fa-users"></i> Usuários</a>
    <a href="logs.php"><i class="fas fa-history"></i> Logs</a>
    <a href="backups.php"><i class="fas fa-database"></i> Backups</a>
    <a href="dashboard.php"><i class="fas fa-chart-bar"></i> Dashboard</a> 
</nav>

        <!-- SEARCH -->
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="search-input" placeholder="Pesquisar imagens...">
        </div>

        <!-- GRID DE IMAGENS -->
        <?php require 'includes/partials/image_grid.php'; ?>

        <!-- PAGINAÇÃO -->
        <?php require 'includes/partials/pagination.php'; ?>
    </div>

    <?php require 'includes/partials/image_modal.php'; ?>

    <script src="assets/js/app.js"></script>

        <!-- AVISO DE SESSÃO EXPIRANDO -->
    <?php if (isLoggedIn()): ?>
        <?php require 'includes/partials/session_warning.php'; ?>
    <?php endif; ?>
</body>
</html>