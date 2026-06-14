<?php


/**
 * Syntara v3.0 — Editar Aula
 *
 * Permite que um professor edite o titulo, conteudo e data de uma aula existente.
 */

require __DIR__ . '/../includes/config.php';
requireLogin('professor');

$professorId = (int) $_SESSION['user_id'];
$aulaId      = inputInt('id');
$error       = '';

if ($aulaId <= 0) {
    setFlash('error', 'Aula nao especificada.');
    redirect('professor/manage_lessons.php');
}

$lesson = new Lesson($pdo);
$aula   = $lesson->findById($aulaId);

if (!$aula) {
    setFlash('error', 'Aula nao encontrada.');
    redirect('professor/manage_lessons.php');
}

// Verifica ownership via curso
$course = new Course($pdo);
$curso  = $course->findById((int) $aula['curso_id']);
if (!$curso || (int) $curso['professor_id'] !== $professorId) {
    setFlash('error', 'Aula nao encontrada.');
    redirect('professor/manage_lessons.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Token de seguranca invalido.';
        saveOldInput();
    } else {
        $titulo   = inputString('titulo');
        $conteudo = inputString('conteudo');
        $dataAula = inputString('data_aula');

        if (!$titulo) {
            $error = 'Informe o titulo da aula.';
            saveOldInput();
        } elseif (mb_strlen($titulo) > 200) {
            $error = 'Titulo muito longo (max. 200 caracteres).';
            saveOldInput();
        } elseif (mb_strlen($conteudo) > 50000) {
            $error = 'Conteudo muito longo (max. 50000 caracteres).';
            saveOldInput();
        } else {
            $lesson->update($aulaId, $titulo, $conteudo, $dataAula);
            setFlash('success', 'Aula atualizada!');
            redirect('professor/manage_lessons.php');
        }
    }
}

$page_title = 'Editar Aula';
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card fade-in" style="max-width:680px;margin:0 auto;">
        <div class="card-header">
            <h2>Editar Aula</h2>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error fade-in" role="alert">
                <span class="alert-icon">❌</span>
                <span class="alert-msg"><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('professor/edit_lesson.php?id=' . (int)$aulaId) ?>">
            <?= csrfField() ?>

            <?php
            $type = 'text'; $name = 'titulo'; $label = 'Titulo da aula'; $value = oldVal('titulo', $aula['titulo']);
            $required = true; $attrs = 'autofocus maxlength="200"';
            require __DIR__ . '/../layouts/form-field.php';
            ?>

            <?php
            $type = 'textarea'; $name = 'conteudo'; $label = 'Conteudo da aula'; $value = oldVal('conteudo', $aula['conteudo']);
            $required = false; $attrs = 'maxlength="50000" rows="10"';
            require __DIR__ . '/../layouts/form-field.php';
            ?>

            <?php
            $type = 'date'; $name = 'data_aula'; $label = 'Data da aula'; $value = oldVal('data_aula', $aula['data_aula']);
            $required = true;
            require __DIR__ . '/../layouts/form-field.php';
            ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
                <a href="<?= url('professor/manage_lessons.php') ?>" class="btn btn-ghost">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
