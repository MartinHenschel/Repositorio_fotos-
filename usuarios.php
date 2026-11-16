<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
requirePermission('usuarios_ver');

/**
 * Lê o array de permissões marcadas no POST (checkboxes permissoes[]).
 */
function permissoesDoPost(): array
{
    $marcadas = $_POST['permissoes'] ?? [];
    return is_array($marcadas) ? $marcadas : [];
}

/**
 * Valida e cria um novo usuário a partir do POST.
 */
function processarCriacaoDeUsuario(): array
{
    requirePermission('usuarios_criar');
    $usuario = trim($_POST['usuario'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $cargo = $_POST['cargo'] ?? 'personalizado';
    $permissoes = permissoesDoPost();

    if ($usuario === '' || $nome === '' || $senha === '') {
        return ['erro' => 'Preencha todos os campos.', 'sucesso' => ''];
    }
    if (strlen($senha) < 6) {
        return ['erro' => 'A senha deve ter pelo menos 6 caracteres.', 'sucesso' => ''];
    }
    if (userExists($usuario)) {
        return ['erro' => 'Já existe um usuário com esse login.', 'sucesso' => ''];
    }

    createUser($usuario, $senha, $nome, $cargo, $permissoes);
    return ['erro' => '', 'sucesso' => 'Usuário criado com sucesso.'];
}

/**
 * Atualiza nome/login/senha de um usuário existente.
 */
function processarEdicaoDeUsuario(): array
{
    requirePermission('usuarios_editar');
    $id = (int)($_POST['id'] ?? 0);
    $usuario = trim($_POST['usuario'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $novaSenha = $_POST['senha'] ?? '';

    if ($usuario === '' || $nome === '') {
        return ['erro' => 'Preencha nome e login.', 'sucesso' => ''];
    }
    if ($novaSenha !== '' && strlen($novaSenha) < 6) {
        return ['erro' => 'A nova senha deve ter pelo menos 6 caracteres.', 'sucesso' => ''];
    }

    updateUserInfo($id, $nome, $usuario, $novaSenha);
    return ['erro' => '', 'sucesso' => 'Usuário atualizado com sucesso.'];
}

/**
 * Atualiza cargo/permissões de um usuário existente.
 */
function processarAtualizacaoDePermissoes(): array
{
    requirePermission('usuarios_editar');
    $id = (int)($_POST['id'] ?? 0);
    $cargo = $_POST['cargo'] ?? 'personalizado';
    $permissoes = permissoesDoPost();

    // Impede que alguém que não seja administrador altere as próprias permissões
    // (evita auto-promoção a um cargo/permissões maiores que o próprio).
    if ($id === (int)$_SESSION['user_id'] && currentUser()['cargo'] !== 'administrador') {
        return ['erro' => 'Você não pode alterar suas próprias permissões.', 'sucesso' => ''];
    }

    updateUserPermissions($id, $cargo, $permissoes);
    return ['erro' => '', 'sucesso' => 'Permissões atualizadas com sucesso.'];
}

/**
 * Exclui um usuário pelo id enviado no POST, impedindo autoexclusão.
 */
function processarExclusaoDeUsuario(): array
{
    requirePermission('usuarios_excluir');
    $id = (int)($_POST['id'] ?? 0);

    if ($id === (int)$_SESSION['user_id']) {
        return ['erro' => 'Você não pode excluir o seu próprio usuário.', 'sucesso' => ''];
    }

    deleteUser($id);
    return ['erro' => '', 'sucesso' => 'Usuário removido.'];
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrfOuFalhar();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        ['erro' => $erro, 'sucesso' => $sucesso] = processarCriacaoDeUsuario();
    } elseif ($acao === 'editar') {
        ['erro' => $erro, 'sucesso' => $sucesso] = processarEdicaoDeUsuario();
    } elseif ($acao === 'atualizar_permissoes') {
        ['erro' => $erro, 'sucesso' => $sucesso] = processarAtualizacaoDePermissoes();
    } elseif ($acao === 'excluir') {
        ['erro' => $erro, 'sucesso' => $sucesso] = processarExclusaoDeUsuario();
    }
}

$usuarios = getAllUsers();
$gruposPermissoes = getPermissoesDisponiveis();
$cargoPresets = getCargoPresets();
$cargoPersonalizado = ['personalizado' => $cargoPresets['personalizado'] ?? []];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Bograntex</title>
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
            <h2>Gerenciar Usuários</h2>
            <div class="stats">
                <span><i class="fas fa-users"></i> <?php echo count($usuarios); ?> usuários</span>
            </div>
        </div>

        <nav class="menu">
            <a href="index.php"><i class="fas fa-th"></i> Todas as imagens</a>
            <a href="albums.php"><i class="fas fa-folder"></i> Álbuns</a>
            <a href="usuarios.php" class="active"><i class="fas fa-users"></i> Usuários</a>
            <a href="logs.php"><i class="fas fa-history"></i> Logs</a>
            <a href="backups.php"><i class="fas fa-database"></i> Backups</a>
            <a href="dashboard.php"><i class="fas fa-chart-bar"></i> Dashboard</a> 
        </nav>

        <?php if ($erro): ?>
            <div class="login-error" style="max-width:700px; margin-bottom:20px;"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="login-success" style="max-width:700px; margin-bottom:20px;"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($sucesso); ?></div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- NOVO USUÁRIO -->
        <!-- ============================================== -->
        <div class="upload-area perm-form-area">
            <h2>Novo usuário</h2>
            <form method="POST" action="usuarios.php">
                <?php echo csrfField(); ?>
                <input type="hidden" name="acao" value="criar">

                <div class="form-group">
                    <label for="nome">Nome completo</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                <div class="form-group">
                    <label for="usuario">Usuário (login)</label>
                    <input type="text" id="usuario" name="usuario" required>
                </div>
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" minlength="6" required>
                </div>

                <div class="perm-divider"></div>

                <div class="form-group">
                    <label>Cargo</label>
                    <div class="cargo-options" data-cargo-group="criar">
                        <?php foreach ($cargoPersonalizado as $chaveCargo => $preset): ?>
                            <label class="cargo-radio">
                                <input type="radio" name="cargo" value="<?php echo htmlspecialchars($chaveCargo); ?>"
                                    class="js-cargo-radio" data-target="criar"
                                    <?php echo $chaveCargo === 'personalizado' ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars(getCargoLabel($chaveCargo)); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="perm-divider"></div>

                <div class="form-group">
                    <label>Permissões</label>
                    <div class="perm-groups" data-perm-group="criar">
                        <?php foreach ($gruposPermissoes as $grupo): ?>
                            <div class="perm-group">
                                <div class="perm-group-title"><i class="fas <?php echo htmlspecialchars($grupo['icon']); ?>"></i> <?php echo htmlspecialchars($grupo['label']); ?></div>
                                <?php foreach ($grupo['itens'] as $chavePerm => $labelPerm): ?>
                                    <label class="perm-item">
                                        <input type="checkbox" name="permissoes[]" value="<?php echo htmlspecialchars($chavePerm); ?>"
                                            class="js-perm-checkbox" data-target="criar"
                                            <?php echo in_array($chavePerm, $cargoPersonalizado['personalizado'], true) ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($labelPerm); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="perm-form-actions">
                    <button type="submit" class="btn-primary"><i class="fas fa-user-plus"></i> Criar Usuário</button>
                </div>
            </form>
        </div>

        <!-- ============================================== -->
        <!-- LISTA DE USUÁRIOS -->
        <!-- ============================================== -->
        <div class="album-list" style="margin-top:24px;">
            <?php foreach ($usuarios as $usuario): ?>
            <div class="user-card">
                <div class="user-card-top">
                    <div class="user-card-who">
                        <i class="fas fa-user"></i>
                        <span class="user-card-name"><?php echo htmlspecialchars($usuario['nome']); ?></span>
                    </div>
                    <span class="cargo-badge cargo-badge-<?php echo htmlspecialchars($usuario['cargo']); ?>">
                        <?php echo htmlspecialchars(getCargoLabel($usuario['cargo'])); ?>
                    </span>
                </div>
                <div class="user-card-sub">
                    login: <?php echo htmlspecialchars($usuario['usuario']); ?> · desde <?php echo htmlspecialchars($usuario['criado_em']); ?>
                    <span class="status-badge status-ativo">Ativo</span>
                </div>

                <div class="perm-summary">
                    <?php foreach ($gruposPermissoes as $grupo):
                        $temAlguma = false;
                        foreach ($grupo['itens'] as $chavePerm => $labelPerm) {
                            if (temPermissao($usuario['permissoes'], $chavePerm)) { $temAlguma = true; break; }
                        }
                    ?>
                        <span class="perm-summary-item <?php echo $temAlguma ? 'is-on' : 'is-off'; ?>">
                            <i class="fas <?php echo $temAlguma ? 'fa-check' : 'fa-xmark'; ?>"></i>
                            <?php echo htmlspecialchars($grupo['label']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>

                <div class="user-card-actions">
                    <button type="button" class="btn-link"
                        onclick='abrirModalEditar(<?php echo (int)$usuario['id']; ?>, <?php echo json_encode($usuario['nome']); ?>, <?php echo json_encode($usuario['usuario']); ?>)'>
                        <i class="fas fa-pen"></i> Editar
                    </button>
                    <button type="button" class="btn-link"
                        onclick='abrirModalPermissoes(<?php echo (int)$usuario['id']; ?>, <?php echo json_encode($usuario['nome']); ?>, <?php echo json_encode($usuario['cargo']); ?>, <?php echo json_encode($usuario['permissoes']); ?>)'>
                        <i class="fas fa-lock"></i> Permissões
                    </button>
                    <form method="POST" action="usuarios.php" onsubmit="return confirm('Remover este usuário?');" style="display:inline;">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                        <button type="submit" class="btn-link btn-link-danger">
                            <i class="fas fa-trash"></i> Excluir
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL: EDITAR DADOS DO USUÁRIO -->
    <!-- ============================================== -->
    <div class="modal-overlay" id="modalEditar">
        <div class="modal">
            <button type="button" class="modal-close" onclick="fecharModal('modalEditar')"><i class="fas fa-xmark"></i></button>
            <h2>Editar usuário</h2>
            <form method="POST" action="usuarios.php">
                <?php echo csrfField(); ?>
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" id="editar-id">
                <div class="form-group">
                    <label for="editar-nome">Nome completo</label>
                    <input type="text" id="editar-nome" name="nome" required>
                </div>
                <div class="form-group">
                    <label for="editar-usuario">Usuário (login)</label>
                    <input type="text" id="editar-usuario" name="usuario" required>
                </div>
                <div class="form-group">
                    <label for="editar-senha">Nova senha (deixe em branco para manter a atual)</label>
                    <input type="password" id="editar-senha" name="senha" minlength="6">
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="fecharModal('modalEditar')">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-check"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL: PERMISSÕES DO USUÁRIO -->
    <!-- ============================================== -->
    <div class="modal-overlay" id="modalPermissoes">
        <div class="modal">
            <button type="button" class="modal-close" onclick="fecharModal('modalPermissoes')"><i class="fas fa-xmark"></i></button>
            <h2>Permissões de <span id="permissoes-nome-usuario"></span></h2>
            <form method="POST" action="usuarios.php">
                <?php echo csrfField(); ?>
                <input type="hidden" name="acao" value="atualizar_permissoes">
                <input type="hidden" name="id" id="permissoes-id">

                <div class="form-group">
                    <label>Cargo</label>
                    <div class="cargo-options" data-cargo-group="permissoes">
                        <?php foreach ($cargoPersonalizado as $chaveCargo => $preset): ?>
                            <label class="cargo-radio">
                                <input type="radio" name="cargo_visivel" value="<?php echo htmlspecialchars($chaveCargo); ?>"
                                    class="js-cargo-radio" data-target="permissoes">
                                <?php echo htmlspecialchars(getCargoLabel($chaveCargo)); ?>
                            </label>
                        <?php endforeach; ?>
                        <input type="hidden" name="cargo" id="permissoes-cargo-atual" value="personalizado">
                    </div>
                </div>

                <div class="perm-divider"></div>

                <div class="form-group">
                    <label>Permissões</label>
                    <div class="perm-groups" data-perm-group="permissoes">
                        <?php foreach ($gruposPermissoes as $grupo): ?>
                            <div class="perm-group">
                                <div class="perm-group-title"><i class="fas <?php echo htmlspecialchars($grupo['icon']); ?>"></i> <?php echo htmlspecialchars($grupo['label']); ?></div>
                                <?php foreach ($grupo['itens'] as $chavePerm => $labelPerm): ?>
                                    <label class="perm-item">
                                        <input type="checkbox" name="permissoes[]" value="<?php echo htmlspecialchars($chavePerm); ?>"
                                            class="js-perm-checkbox" data-target="permissoes">
                                        <?php echo htmlspecialchars($labelPerm); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" onclick="fecharModal('modalPermissoes')">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-check"></i> Salvar permissões</button>
                </div>
            </form>
        </div>
    </div>

    <!-- AVISO DE SESSÃO EXPIRANDO -->
    <?php if (isLoggedIn()): ?>
        <?php require 'includes/partials/session_warning.php'; ?>
    <?php endif; ?>

    <script>
        // Presets de permissão por cargo, gerados a partir do PHP
        const CARGO_PRESETS = <?php echo json_encode($cargoPersonalizado); ?>;

        // Ao escolher um cargo, pré-marca as caixinhas do grupo correspondente
        // (form "criar" ou modal "permissoes"). O usuário ainda pode ajustar manualmente depois.
        document.querySelectorAll('.js-cargo-radio').forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (!this.checked) return;
                const target = this.dataset.target;
                const preset = CARGO_PRESETS[this.value] || [];

                document.querySelectorAll('.js-perm-checkbox[data-target="' + target + '"]').forEach(function (checkbox) {
                    checkbox.checked = preset.includes(checkbox.value);
                });

                // Reforça o destaque visual do card selecionado (compatibilidade com :has() do CSS)
                document.querySelectorAll('.js-cargo-radio[data-target="' + target + '"]').forEach(function (r) {
                    r.closest('.cargo-radio').classList.toggle('is-selected', r.checked);
                    if (target === 'permissoes' && r.checked) {
                        document.getElementById('permissoes-cargo-atual').value = r.value;
                    }
                });
            });
        });

        function abrirModalEditar(id, nome, usuario) {
            document.getElementById('editar-id').value = id;
            document.getElementById('editar-nome').value = nome;
            document.getElementById('editar-usuario').value = usuario;
            document.getElementById('editar-senha').value = '';
            document.getElementById('modalEditar').classList.add('active');
        }

        function abrirModalPermissoes(id, nome, cargoAtual, permissoesAtuais) {
            document.getElementById('permissoes-id').value = id;
            document.getElementById('permissoes-nome-usuario').textContent = nome;

            document.getElementById('permissoes-cargo-atual').value = cargoAtual || 'personalizado';
            document.querySelectorAll('.js-cargo-radio[data-target="permissoes"]').forEach(function (radio) {
                radio.checked = (radio.value === cargoAtual);
            });
            document.querySelectorAll('.js-perm-checkbox[data-target="permissoes"]').forEach(function (checkbox) {
                checkbox.checked = permissoesAtuais.includes(checkbox.value);
            });

            document.getElementById('modalPermissoes').classList.add('active');
        }

        function fecharModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        // Aplica o destaque no cargo que já vier marcado ao carregar a página
        document.querySelectorAll('.js-cargo-radio:checked').forEach(function (r) {
            r.closest('.cargo-radio').classList.add('is-selected');
        });

        // Fecha modal clicando fora dele
        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) overlay.classList.remove('active');
            });
        });
    </script>
</body>
</html>