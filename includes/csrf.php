<?php
/**
 * Proteção CSRF (Cross-Site Request Forgery).
 *
 * Gera um token único por sessão e garante que todo POST que altera dados
 * (criar/excluir usuário, criar/excluir álbum, login) só é aceito se vier
 * acompanhado do token correto — o que só acontece se o formulário foi
 * realmente carregado pelo nosso próprio site, na mesma sessão do usuário.
 */

function csrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Imprime o campo escondido a ser colocado dentro de cada <form method="POST">.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Verifica o token recebido no POST contra o da sessão.
 * Encerra a requisição com 403 se o token estiver ausente ou não bater.
 */
function verificarCsrfOuFalhar(): void
{
    $tokenEsperado = $_SESSION['csrf_token'] ?? '';
    $tokenRecebido = $_POST['csrf_token'] ?? '';

    if ($tokenEsperado === '' || !hash_equals($tokenEsperado, $tokenRecebido)) {
        http_response_code(403);
        die('Requisição inválida ou expirada (token CSRF ausente/incorreto). Volte à página e tente novamente.');
    }
}
