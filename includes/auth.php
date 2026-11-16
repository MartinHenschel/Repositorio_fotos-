<?php
require_once __DIR__ . '/csrf.php';

// Inicia a sessão apenas se ainda não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    // ===== CONFIGURAÇÃO DE SESSÃO =====
    session_set_cookie_params([
        'lifetime' => 4000, // Cookie expira em 1 hora
        'path' => '/', 
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443,
        'httponly' => true, // JavaScript não pode acessar
        'samesite' => 'Lax' // Proteção CSRF
    ]);
    
    session_start();
}

/**
 * Verifica se existe um usuário logado na sessão atual
 * e se esse usuário ainda existe de fato no banco.
 * TAMBÉM VERIFICA TIMEOUT DE INATIVIDADE.
 */
function isLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // ===== VERIFICAR TIMEOUT DE INATIVIDADE =====
    $timeout = 4000; // 1 hora em segundos
    
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        
        // Se passou mais de 1 hora sem atividade, deslogar
        if ($elapsed > $timeout) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    // Atualizar último acesso (AGORA)
    $_SESSION['last_activity'] = time();
    
    // Confirma que o usuário da sessão realmente ainda existe no banco
    if (!getUserById($_SESSION['user_id'])) {
        session_unset();
        session_destroy();
        return false;
    }
    
    return true;
}

/**
 * Bloqueia o acesso à página se o usuário não estiver logado.
 * Deve ser chamado no topo de toda página protegida, ANTES de qualquer saída HTML.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Retorna os dados do usuário logado (armazenados na sessão no login)
 */
function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    // Busca o usuário direto no banco (não só na sessão) para que cargo/permissões
    // alterados por um administrador valham imediatamente, sem precisar deslogar.
    $user = getUserById($_SESSION['user_id']);
    if (!$user) {
        return null;
    }

    return [
        'id' => $user['id'],
        'usuario' => $user['usuario'],
        'nome' => $user['nome'],
        'cargo' => $user['cargo'] ?? 'operador',
        'permissoes' => json_decode($user['permissoes'] ?? '[]', true) ?: [],
    ];
}
/**
 * Verifica se o usuário logado tem uma permissão específica.
 * Administradores sempre têm acesso total, independente da lista salva.
 */                                     
/**
 * Verifica se o usuário logado tem uma permissão específica.
 * Administradores sempre têm acesso total, independente da lista salva.
 */
function usuarioTemPermissao(string $chave): bool
{ 
    $user = currentUser();
    if (!$user) {
        return false;
    }
    if ($user['cargo'] === 'administrador') {
        return true;
    }
    return temPermissao($user['permissoes'], $chave);
}

/**
 * Bloqueia o acesso à página/ação se o usuário logado não tiver a permissão informada.
 * Deve ser chamado logo no topo da página (ou antes de processar uma ação específica),
 * depois de requireLogin() já ter garantido que existe alguém logado.
 */
function requirePermission(string $chave): void
{
    requireLogin();

    if (!usuarioTemPermissao($chave)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
           . '<title>Acesso negado - Bograntex</title>'
           . '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">'
           . '<link rel="stylesheet" href="assets/css/style.css"></head>'
           . '<body><div class="container" style="max-width:500px; margin-top:80px; text-align:center;">'
           . '<div class="login-error" style="justify-content:center; font-size:16px; padding:20px;">'
           . '<i class="fas fa-lock"></i> Você não tem permissão para acessar esta área.'
           . '</div>'
           . '<p style="margin-top:20px;"><a href="index.php" class="btn-primary" style="text-decoration:none; display:inline-block;">'
           . '<i class="fas fa-arrow-left"></i> Voltar</a></p>'
           . '</div></body></html>';
        exit;
    }
}

/**
 * Retorna o tempo restante de sessão em minutos
 */
function getSessionTimeRemaining() {
    if (!isset($_SESSION['last_activity'])) {
        return 60; // 1 hora
    }
    
    $timeout = 3600; // 1 hora
    $elapsed = time() - $_SESSION['last_activity'];
    $remaining = $timeout - $elapsed;
    
    return max(0, ceil($remaining / 60)); // Retorna em minutos
}
function getLogoutButton() {
    $user = currentUser();
    if (!$user) {
        return '';
    }
    
    return '
        <div class="user-menu" style="display: flex; align-items: center; gap: 15px;">
            <span style="color: #fff; font-size: 14px;">
                <i class="fas fa-user-circle"></i> ' . htmlspecialchars($user['nome']) . '
            </span>
            <form method="POST" action="logout.php" style="display:inline; margin:0;">
                ' . csrfField() . '
                <button type="submit" class="btn-logout" style="
                background: #dc3545;
                color: white;
                padding: 8px 16px;
                border-radius: 4px;
                border: none;
                font-size: 14px;
                font-family: inherit;
                transition: background 0.3s;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
            " onmouseover="this.style.background=\'#c82333\'" onmouseout="this.style.background=\'#dc3545\'">
                <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </form>
        </div>
    ';
}
?>