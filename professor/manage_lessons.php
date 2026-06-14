<?php

/**
 * Syntara v3.0 — Gerenciar Aulas
 *
 * Lista todas as aulas do professor, com filtro por curso.
 */

require __DIR__ . '/../includes/config.php';
requireLogin('professor');

$professorId = (int) $_SESSION['user_id'];
$error       = '';

$courseModel = new Course($pdo);
$cursos      = $courseModel->findAll($professorId);

if (empty($cursos)) {
    setFlash('error', 'Voce nao tem nenhum curso. Crie um curso antes de gerenciar aulas.');
    redirect('professor/create_course.php');
}

$cursoOptions = ['' => 'Todos os cursos'];
foreach ($cursos as $c) $cursoOptions[$c['id']] = $c['nome'];

$filterCurso = inputInt('curso_id');

if ($filterCurso > 0) {
    $cursoCheck = $courseModel->findById($filterCurso);
    if (!$cursoCheck || (int) $cursoCheck['professor_id'] !== $professorId) {
        $filterCurso = 0;
        $error = 'Curso invalido.';
    }
}

$lesson = new Lesson($pdo);
if ($filterCurso > 0) {
    $lessons = $lesson->findByCourse($filterCurso);
    // Garante que todas as aulas retornadas pertencem ao professor
    $lessons = array_values(array_filter($lessons, function ($l) use ($filterCurso) {
        return (int) $l['curso_id'] === $filterCurso;
    }));
} else {
    // Busca aulas de todos os cursos do professor
    $cursoIds = array_column($cursos, 'id');
    $lessons  = $lesson->findByCourses($cursoIds);
}

$page_title = 'Gerenciar Aulas';
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card fade-in">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div>
                <h2>Minhas Aulas</h2>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="<?= url('professor/create_course.php') ?>" class="btn btn-sm btn-secondary">+ Novo Curso</a>
                <a href="<?= url('professor/create_lesson.php') ?>" class="btn btn-sm btn-primary">+ Nova Aula</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error fade-in" role="alert">
                <span class="alert-icon">❌</span>
                <span class="alert-msg"><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Filtro -->
        <div class="mb-2">
            <form method="GET" action="<?= url('professor/manage_lessons.php') ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;">
                    <?php
                    $type = 'select'; $name = 'curso_id'; $label = ''; $value = $filterCurso;
                    $options = $cursoOptions; $attrs = 'onchange="this.form.submit()"';
                    require __DIR__ . '/../layouts/form-field.php';
                    ?>
                </div>
                <?php if ($filterCurso): ?>
                    <a href="<?= url('professor/manage_lessons.php') ?>" class="btn btn-sm btn-ghost">Limpar</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($lessons)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <p><?= $filterCurso ? 'Nenhuma aula encontrada para este curso.' : 'Voce ainda nao criou nenhuma aula.' ?></p>
                <a href="<?= url('professor/create_lesson.php' . ($filterCurso ? '?curso_id=' . (int)$filterCurso : '')) ?>" class="btn btn-primary">
                    Criar primeira aula
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Aula</th>
                            <th>Curso</th>
                            <th>Data</th>
                            <th style="width:120px">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($lessons as $l): ?>
                        <tr>
                            <td><strong><?= e($l['titulo']) ?></strong></td>
                            <td><?= e($l['curso_nome'] ?? '') ?></td>
                            <td><?= date('d/m/Y', strtotime($l['data_aula'])) ?></td>
                            <td>
                                <div class="btn-group" style="gap:6px;">
                                    <a href="<?= url('professor/edit_lesson.php?id=' . (int)$l['id']) ?>" class="btn btn-sm btn-secondary" title="Editar">✏️</a>
                                    <a href="<?= url('professor/delete_lesson.php?id=' . (int)$l['id']) ?>" class="btn btn-sm btn-danger btn-delete" title="Excluir">🗑️</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
