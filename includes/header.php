<?php

if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/config.php';
}

$pageTitle = $page_title ?? SITE_NAME;

$userType = $_SESSION['user_type'] ?? null;
$userName = $_SESSION['user_nome'] ?? 'Visitante';

$bodyClass =
    ($body_class ?? '') .
    ' ' .
    (($_COOKIE['syntara_dark'] ?? '0') === '1' ? 'dark' : '');

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="<?= SITE_NAME ?> — Sistema de Avaliação Escolar">

    <title><?= e($pageTitle) ?> — <?= SITE_NAME ?></title>

    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <script src="<?= url('assets/js/main.js') ?>" defer></script>

</head>

<body class="<?= e(trim($bodyClass)) ?>">

<header class="navbar" id="header">

    <div class="nav-inner">

        <a href="<?= url('index.php') ?>" class="logo">
            <span class="logo-icon">🎓</span>
            <?= SITE_NAME ?>
        </a>

        <button class="theme-toggle" onclick="toggleDark()" aria-label="Alternar tema">
            🌙
        </button>

        <button class="nav-toggle" onclick="toggleMenu()" aria-label="Abrir menu">
            ☰
        </button>

        <nav class="nav-links" id="navLinks">

            <!-- SEM LOGIN -->
            <?php if (!isLoggedIn()): ?>

                <a href="<?= url('index.php') ?>" class="nav-link">Início</a>

                <a href="<?= url('denuncia.php') ?>" class="nav-link">🛡️ Denúncia</a>

                <a href="<?= url('login.php') ?>" class="nav-link">Entrar</a>

                <a href="<?= url('register.php') ?>" class="nav-link btn-nav">Criar Conta</a>

            <?php else: ?>

                <!-- LOGADO -->
                <a href="<?= url('index.php') ?>" class="nav-link">Início</a>

                <?php if ($userType === 'aluno'): ?>
                    <a href="<?= url('aluno/agenda.php') ?>" class="nav-link">📅 Agenda</a>

                <?php elseif ($userType === 'professor'): ?>
                    <a href="<?= url('agenda.php') ?>" class="nav-link">📅 Agenda</a>

                <?php endif; ?>

                <span class="nav-link user-name">
                    👤 <?= e($userName) ?>
                </span>

                <a href="<?= url('logout.php') ?>" class="nav-link btn-danger">
                    Sair
                </a>

            <?php endif; ?>

        </nav>

    </div>

</header>

<div class="container" style="padding-top:16px;">
    <?php require_once ROOT_PATH . '/layouts/alert.php'; ?>
</div>

<main class="content">