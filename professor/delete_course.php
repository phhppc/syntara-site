<?php


/**
 * Syntara v3.0 — Excluir Curso
 *
 * Permite que um professor exclua um curso e todas as aulas associadas.
 */

require __DIR__ . '/../includes/config.php';
requireLogin('professor');

$professorId = (int) $_SESSION['user_id'];
$cursoId     = inputInt('id');

if ($cursoId <= 0) {
    setFlash('error', 'Curso nao especificado.');
    redirect('professor/dashboard.php');
}

$course = new Course($pdo);
$curso  = $course->findById($cursoId);

if (!$curso || (int) $curso['professor_id'] !== $professorId) {
    setFlash('error', 'Curso nao encontrado ou sem permissao.');
    redirect('professor/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('error', 'Token de seguranca invalido.');
        redirect('professor/dashboard.php');
    }

    $course->delete($cursoId);

    setFlash('success', 'Curso excluido com sucesso!');
    redirect('professor/dashboard.php');
}

$page_title = 'Excluir Curso';
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card fade-in" style="max-width:480px;margin:0 auto;text-align:center;">
        <div class="card-header" style="justify-content:center;">
            <h2>Excluir Curso</h2>
        </div>

        <div class="mb-2">
            <p style="font-size:1.05rem;">Deseja excluir o curso <strong style="color:var(--wine);"><?= e($curso['nome']) ?></strong>?</p>
            <p class="text-muted text-sm">Todas as aulas, matriculas e feedbacks associados tambem serao removidos.</p>
        </div>

        <form method="POST" action="<?= url('professor/delete_course.php?id=' . (int)$cursoId) ?>">
            <?= csrfField() ?>
            <div class="form-actions" style="justify-content:center;">
                <button type="submit" class="btn btn-danger">Sim, excluir curso</button>
                <a href="<?= url('professor/dashboard.php') ?>" class="btn btn-ghost">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
