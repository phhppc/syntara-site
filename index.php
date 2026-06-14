<?php

require __DIR__ . '/includes/config.php';

if (isLoggedIn()) {

    if ($_SESSION['user_type'] === 'admin') {
        $redirect = 'admin/dashboard.php';

    } elseif ($_SESSION['user_type'] === 'professor') {
        $redirect = 'professor/dashboard.php';

    } elseif ($_SESSION['user_type'] === 'aluno') {
        $redirect = 'aluno/dashboard.php';

    } else {
        $redirect = 'index.php';
    }

    redirect($redirect);
}

$page_title = 'Início';

require __DIR__ . '/includes/header.php';
?>

<div class="hero fade-in">

    <h2>Sistema de Avaliação Escolar</h2>

    <p>
        Gerencie cursos, aulas, avaliações e feedbacks em uma plataforma moderna e segura.
    </p>

    <div class="btn-group" style="justify-content:center;">

        <a href="<?= url('login.php') ?>" class="btn btn-primary btn-lg">
            Entrar
        </a>

        <a href="<?= url('register.php') ?>" class="btn btn-secondary btn-lg">
            Criar Conta
        </a>

    </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>