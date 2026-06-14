<?php
/**
 * Syntara v3.0 — Logout
 *
 * Encerra a sessão do usuário, remove cookies e redireciona para o index.
 *
 * @package Syntara
 */



require_once 'includes/config.php';

// Limpa todos os dados da sessão
$_SESSION = [];

// Remove cookie da sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão completamente
session_destroy();

// Redireciona para o index
redirect('index.php');
