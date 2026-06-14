<?php

/**
 * Syntara v3.0 — Editar Curso
 *
 * Permite que um professor edite o nome e a descricao de um curso existente.
 */

require __DIR__ . '/../includes/config.php';
requireLogin('professor');

$professorId = (int) $_SESSION['user_id'];
$cursoId     = inputInt('id');
$error       = '';

if ($cursoId <= 0) {
    setFlash('error', 'Curso nao especificado.');
    redirect('professor/dashboard.php');
}

$course = new Course($pdo);
$curso  = $course->findById($cursoId);

if (!$curso || (int) $curso['professor_id'] !== $professorId) {
    setFlash('error', 'Curso nao encontrado.');
    redirect('professor/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Token de seguranca invalido.';
        saveOldInput();
    } else {
        $nome      = inputString('nome');
        $descricao = inputString('descricao');

        if (!$nome) {
            $error = 'Informe o nome do curso.';
            saveOldInput();
        } elseif (mb_strlen($nome) > 200) {
            $error = 'Nome muito longo (max. 200 caracteres).';
            saveOldInput();
        } elseif (mb_strlen($descricao) > 2000) {
            $error = 'Descricao muito longa (max. 2000 caracteres).';
            saveOldInput();
        } else {
            $course->update($cursoId, $nome, $descricao);

            setFlash('success', 'Curso atualizado!');
            redirect('professor/dashboard.php');
        }
    }
}

$page_title = 'Editar Curso';
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card fade-in" style="max-width:600px;margin:0 auto;">
        <div class="card-header">
            <h2>Editar Curso</h2>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error fade-in" role="alert">
                <span class="alert-icon">❌</span>
                <span class="alert-msg"><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('professor/edit_course.php?id=' . (int)$cursoId) ?>">
            <?= csrfField() ?>

            <?php
            $type = 'text'; $name = 'nome'; $label = 'Nome do curso'; $value = oldVal('nome', $curso['nome']);
            $required = true; $attrs = 'autofocus maxlength="200"';
            require __DIR__ . '/../layouts/form-field.php';
            ?>

            <?php
            $type = 'textarea'; $name = 'descricao'; $label = 'Descricao (opcional)'; $value = oldVal('descricao', $curso['descricao']);
            $required = false; $attrs = 'maxlength="2000" rows="4"';
            require __DIR__ . '/../layouts/form-field.php';
            ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
                <a href="<?= url('professor/dashboard.php') ?>" class="btn btn-ghost">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
