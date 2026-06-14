<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('aluno');

$courseRepo = new Course($pdo);
$lessonRepo = new Lesson($pdo);
$enrollment = new Enrollment($pdo);

$alunoId  = (int) $_SESSION['user_id'];

$cursoId  = (int) ($_GET['curso_id'] ?? 0);
$lessonId = (int) ($_GET['id'] ?? 0);

/*
|--------------------------------------------------------------------------
| VALIDA CURSO
|--------------------------------------------------------------------------
*/

if ($cursoId > 0 && !$courseRepo->findById($cursoId)) {
    die("Curso inválido ou não existe.");
}

/*
|--------------------------------------------------------------------------
| LISTA DE AULAS DO CURSO
|--------------------------------------------------------------------------
*/

if ($cursoId > 0 && $lessonId <= 0) {

    if (!$enrollment->isActive($alunoId, $cursoId)) {
        setFlash('error', 'Você não está matriculado neste curso.');
        redirect('aluno/dashboard.php');
    }

    $curso = $courseRepo->findById($cursoId);
    $aulas = $lessonRepo->findByCourse($cursoId);

    $page_title = 'Aulas — ' . ($curso['nome'] ?? 'Curso');

    require __DIR__ . '/../includes/header.php';
    ?>

    <div class="container">

        <div class="card">
            <div class="card-header">
                <div>
                    <h2>📖 <?= e($curso['nome']) ?></h2>
                    <p><?= e($curso['descricao'] ?? '') ?></p>
                </div>

                <a href="dashboard.php" class="btn btn-sm btn-secondary">
                    ← Voltar
                </a>
            </div>

            <?php if (empty($aulas)): ?>
                <div class="empty-state">
                    <p>Nenhuma aula disponível.</p>
                </div>
            <?php else: ?>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Aula</th>
                            <th>Data</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($aulas as $aula): ?>
                        <tr>
                            <td><?= e($aula['titulo']) ?></td>
                            <td><?= e($aula['data_aula']) ?></td>
                            <td>
                                <a class="btn btn-sm btn-primary"
                                   href="view_lesson.php?id=<?= (int)$aula['id'] ?>">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

        </div>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; return;
}

/*
|--------------------------------------------------------------------------
| AULA INDIVIDUAL
|--------------------------------------------------------------------------
*/

if ($lessonId <= 0) {
    die("Aula inválida.");
}

$aula = $lessonRepo->findById($lessonId);

if (!$aula) {
    die("Aula não encontrada.");
}

if (!$enrollment->isActive($alunoId, (int)$aula['curso_id'])) {
    die("Acesso negado.");
}

$curso = $courseRepo->findById((int)$aula['curso_id']);

$page_title = $aula['titulo'];

require __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <div class="card">

        <div class="card-header">
            <div>
                <h2>📖 <?= e($aula['titulo']) ?></h2>
                <p>
                    <a href="view_lesson.php?curso_id=<?= (int)$aula['curso_id'] ?>">
                        <?= e($curso['nome'] ?? 'Curso') ?>
                    </a>
                    • <?= e($aula['data_aula']) ?>
                </p>
            </div>

            <a href="view_lesson.php?curso_id=<?= (int)$aula['curso_id'] ?>"
               class="btn btn-secondary btn-sm">
                ← Voltar
            </a>
        </div>

        <div class="lesson-content">
            <?= nl2br(e($aula['conteudo'] ?? 'Sem conteúdo')) ?>
        </div>

    </div>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>