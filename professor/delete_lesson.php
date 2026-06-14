<?php


/**
 * Syntara v3.0 — Excluir Aula
 *
 * Permite que um professor exclua uma aula existente.
 */

require __DIR__ . '/../includes/config.php';
requireLogin('professor');

$professorId = (int) $_SESSION['user_id'];
$aulaId      = inputInt('id');

if ($aulaId <= 0) {
    setFlash('error', 'Aula nao especificada.');
    redirect('professor/manage_lessons.php');
}

$lesson = new Lesson($pdo);
$aula   = $lesson->findById($aulaId);

if (!$aula) {
    setFlash('error', 'Aula nao encontrada ou sem permissao.');
    redirect('professor/manage_lessons.php');
}

// Verifica ownership via curso
$course = new Course($pdo);
$curso  = $course->findById((int) $aula['curso_id']);
if (!$curso || (int) $curso['professor_id'] !== $professorId) {
    setFlash('error', 'Aula nao encontrada ou sem permissao.');
    redirect('professor/manage_lessons.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('error', 'Token de seguranca invalido.');
        redirect('professor/manage_lessons.php');
    }

    $lesson->delete($aulaId);

    setFlash('success', 'Aula excluida com sucesso!');
    redirect('professor/manage_lessons.php');
}

$page_title = 'Excluir Aula';
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card fade-in" style="max-width:480px;margin:0 auto;text-align:center;">
        <div class="card-header" style="justify-content:center;">
            <h2>Excluir Aula</h2>
        </div>

        <div class="mb-2">
            <p style="font-size:1.05rem;">Deseja excluir a aula <strong style="color:var(--wine);"><?= e($aula['titulo']) ?></strong>?</p>
            <p class="text-muted text-sm">Curso: <?= e($aula['curso_nome'] ?? '') ?></p>
        </div>

        <form method="POST" action="<?= url('professor/delete_lesson.php?id=' . (int)$aulaId) ?>">
            <?= csrfField() ?>
            <div class="form-actions" style="justify-content:center;">
                <button type="submit" class="btn btn-danger">Sim, excluir aula</button>
                <a href="<?= url('professor/manage_lessons.php') ?>" class="btn btn-ghost">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
