<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

requirePermission('imagens_upload');
$totalAlbuns = countAlbums();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrfToken()); ?>">
    <title>Bograntex</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/catalogo.css">
</head>
<body>
    <!-- TOP BAR  -->
    <header class="topbar">
    <div class="logo">BogranTex</div>
    <div class="topbar-actions">
        <a href="upload.php" class="btn-primary"><i class="fas fa-upload"></i> Enviar</a>
        <?php echo getLogoutButton(); ?>
    </div>
</header>

    <nav class="menu">
        <a href="index.php"><i class="fas fa-th"></i> Voltar</a>
    </nav>

    <main class="container">
        <!-- Card principal -->
        <div class="card">
            <h2>Envie fotos para nosso banco</h2>
            <p class="subtitle">Selecione as fotos e envie direto para o catálogo. Confira antes se o nome da foto é o mesmo da referência.</p>
        </div>

        <!-- Card de seleção -->
        <div class="card">
            <div class="actions-top">
                <button id="btnEscolher" class="btn-primary">📁 Escolher foto(s)</button>
                <button id="btnLimpar" class="btn-secondary">Limpar tudo</button>
                <span id="contador" class="contador">Nenhuma foto selecionada</span>
            </div>

            <div class="file-list-wrapper">
                <ul id="listaFotos" class="file-list"></ul>
                <p class="hint">(clique duas vezes num item para removê-lo)</p>
            </div>
        </div>

        <!-- Card de opções -->
        <div class="card">
            <div class="option-row">
                <label>
                    <input type="checkbox" id="chkSubstituir" />
                    Substituir imagens já existentes (mesmo nome de arquivo)
                </label>
            </div>

            <div class="album-row">
                <label for="comboAlbum">Enviar para o álbum:</label>
                <select id="comboAlbum" class="album-select"></select>
                <button id="btnCriarAlbum" type="button" class="btn-secondary small">
                    <i class="fas fa-plus"></i> Criar álbum
                </button>
              <button id="btnAtualizarAlbuns" class="btn-secondary small">Atualizar álbuns</button>
            </div>
            <form id="formCriarAlbum" class="album-create-form" hidden>
                <label for="nomeNovoAlbum">Nome da nova Coleção</label>
                <input type="text" id="nomeNovoAlbum" maxlength="120" required>
                <button type="submit" class="btn-primary small"><i class="fas fa-check"></i> Salvar álbum</button>
                <button id="btnCancelarCriarAlbum" type="button" class="btn-secondary small">Cancelar</button>
            </form>
        </div>

        <!-- Status e botão enviar -->
        <div id="status" class="status"></div>
        <button id="btnEnviar" class="btn-primary submit" disabled>Enviar</button>
    </main>

    <input type="file" id="fileInput" accept="image/*" multiple style="display:none" />

    <!-- JavaScript do catálogo (mesma lógica usada em catalogo.php) -->
    <script src="assets/js/catalogo.js"></script>

    <!-- AVISO DE SESSÃO EXPIRANDO -->
    <?php if (isLoggedIn()): ?>
        <?php require 'includes/partials/session_warning.php'; ?>
    <?php endif; ?>
</body>
</html>