<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	header('Allow: POST');
	exit('Método não permitido.');
}

verificarCsrfOuFalhar();

// Destruir sessão
session_unset();
session_destroy();

// Redirecionar para login
header('Location: login.php');
exit;
?>