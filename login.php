<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
require_once 'includes/RateLimit.php';

// Se já está logado, manda direto pra home
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$rateLimit = new RateLimit();
$ip = $_SERVER['REMOTE_ADDR'];
$erro = '';
$tentativasRestantes = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ===== VERIFICAR RATE LIMIT =====
    if (!$rateLimit->isAllowed($ip)) {
        $timeRemaining = $rateLimit->getTimeRemaining($ip);
        $minutesRemaining = ceil($timeRemaining / 60);
        $erro = "Muitas tentativas de login. Tente novamente em {$minutesRemaining} minuto(s).";
    } else {
        verificarCsrfOuFalhar();
        $usuario = trim($_POST['usuario'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if ($usuario === '' || $senha === '') {
            $erro = 'Preencha usuário e senha.';
            $rateLimit->recordAttempt($ip);
            $tentativasRestantes = $rateLimit->getAttemptCount($ip);
        } else {
            $user = authenticateUser($usuario, $senha);
            if ($user) {
                // Login bem-sucedido: limpar tentativas
                $rateLimit->clearAttempts($ip);
                
                // Evita fixação de sessão
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['usuario'] = $user['usuario'];
                $_SESSION['nome'] = $user['nome'];
                header('Location: index.php');
                exit;
            } else {
                // Login falhado: registrar tentativa
                $rateLimit->recordAttempt($ip);
                $tentativasRestantes = $rateLimit->getAttemptCount($ip);
                $erro = "Usuário ou senha inválidos. ({$tentativasRestantes}/5 tentativas)";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bograntex</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-logo">Bogran<span>Tex</span></div>
            <p class="login-subtitle">Acesso restrito</p>

            <?php if ($erro): ?>
                <div class="login-error"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="login-form">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label for="usuario">Usuário</label>
                    <input type="text" id="usuario" name="usuario" autocomplete="username" required autofocus <?php echo !$rateLimit->isAllowed($ip) ? 'disabled' : ''; ?>>
                </div>
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" autocomplete="current-password" required <?php echo !$rateLimit->isAllowed($ip) ? 'disabled' : ''; ?>>
                </div>
                <button type="submit" class="btn-primary btn-login" <?php echo !$rateLimit->isAllowed($ip) ? 'disabled' : ''; ?>>
                    <i class="fas fa-right-to-bracket"></i> Entrar
                </button>
            </form>
        </div>
    </div>
</body>
</html>