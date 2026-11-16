<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

requirePermission('albuns_ver');

function processarCriacaoDeAlbum(): void
{
    requirePermission('albuns_criar');
    verificarCsrfOuFalhar();
    $nome = trim($_POST['nome_album'] ?? '');
    if (!empty($nome)) {
        createAlbum($nome);
        header('Location: albums.php');
        exit;
    }
}

function processarExclusaoDeAlbum(): void
{
    requirePermission('albuns_excluir');
    verificarCsrfOuFalhar();
    $id = (int)($_POST['album_id'] ?? 0);
    if ($id > 0) {
        deleteAlbum($id);
    }
    header('Location: albums.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['novo_album'])) {
        processarCriacaoDeAlbum();
    }
    if (isset($_POST['excluir_album'])) {
        processarExclusaoDeAlbum();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');
}

$albuns = getAllAlbums();
$totalImagens = countImages();
$totalAlbuns = countAlbums(); // ← corrigido
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Álbuns - BogranTex</title>
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
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
            <h2>Álbuns</h2>
            <div class="stats">
                <span><i class="fas fa-image"></i> <?php echo $totalImagens; ?> imagens</span>
                <span><i class="fas fa-folder"></i> <?php echo $totalAlbuns; ?> álbuns</span>
            </div>
        </div>

        <nav class="menu">
            <a href="index.php"><i class="fas fa-th"></i> Todas as imagens</a>
            <a href="albums.php" class="active"><i class="fas fa-folder"></i> Álbuns</a>
            <a href="usuarios.php"><i class="fas fa-users"></i> Usuários</a>
            <a href="logs.php"><i class="fas fa-history"></i> Logs</a>
            <a href="backups.php"><i class="fas fa-database"></i> Backups</a>
            <a href="dashboard.php"><i class="fas fa-chart-bar"></i> Dashboard</a>
        </nav>

        <?php if (usuarioTemPermissao('albuns_criar')): ?>
            <button class="new-album-btn" id="btn-novo-album"><i class="fas fa-plus"></i> Novo Álbum</button>
            <div id="modal-novo-album" style="display:none; background:#fff; padding:20px; border-radius:12px; margin-bottom:20px;">
                <form method="POST" action="albums.php">
                    <?php echo csrfField(); ?>
                    <input type="text" name="nome_album" placeholder="Nome do álbum" required>
                    <button type="submit" name="novo_album" class="btn-primary">Criar</button>
                    <button type="button" id="cancelar-novo-album" class="btn-secondary">Cancelar</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="album-list">
            <?php foreach ($albuns as $album):
                $quantidadeDeImagens = countImagesInAlbum($album['id']);
            ?>
                <a href="album.php?id=<?php echo $album['id']; ?>" style="text-decoration: none; color: inherit;">
                    <div class="album-item" data-id="<?php echo $album['id']; ?>">
                        <div class="cover">
                            <?php if ($album['capa']): ?>
                                <img src="<?php echo $album['capa']; ?>" alt="<?php echo htmlspecialchars($album['nome']); ?>">
                            <?php else: ?>
                                📁
                            <?php endif; ?>
                        </div>
                        <div class="info">
                            <div class="name"><?php echo htmlspecialchars($album['nome']); ?></div>
                            <div class="count"><?php echo $quantidadeDeImagens; ?> imagem<?php echo $quantidadeDeImagens !== 1 ? 's' : ''; ?></div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <?php if (usuarioTemPermissao('albuns_excluir')): ?>
                                <form method="POST"
                                      onsubmit="return confirm('Deseja realmente excluir este álbum?');"
                                      onclick="event.stopPropagation();">
                                    <input type="hidden" name="album_id" value="<?php echo $album['id']; ?>">
                                    <?php echo csrfField(); ?>
                                    <button type="submit"
                                            name="excluir_album"
                                            style="background:#e53935; color:#fff; border:none; width:36px; height:36px; border-radius:8px; cursor:pointer;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <div class="arrow">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        const btnNovoAlbum = document.getElementById('btn-novo-album');
        const modalNovoAlbum = document.getElementById('modal-novo-album');
        const btnCancelarNovoAlbum = document.getElementById('cancelar-novo-album');

        if (btnNovoAlbum && modalNovoAlbum) {
            btnNovoAlbum.addEventListener('click', function() {
                modalNovoAlbum.style.display = 'block';
            });
        }
        if (btnCancelarNovoAlbum && modalNovoAlbum) {
            btnCancelarNovoAlbum.addEventListener('click', function() {
                modalNovoAlbum.style.display = 'none';
            });
        }
    </script>
    <script src="assets/js/app.js"></script>

    <?php if (isLoggedIn()): ?>
        <?php require 'includes/partials/session_warning.php'; ?>
    <?php endif; ?>
</body>
</html>