<?php


/**
 * Syntara v3.0 — Painel do Professor
 *
 * Dashboard principal com estatisticas de cursos, alunos e aulas,
 * lista de cursos com acoes e aulas recentes.
 */

require __DIR__ . '/../includes/config.php';
requireLogin('professor');

$professorId = (int) $_SESSION['user_id'];

// Course model
$course     = new Course($pdo);
$cursos     = $course->findAll($professorId);
$cursosCount = count($cursos);

// Enrollment model — total active students across all professor courses
$enrollment  = new Enrollment($pdo);
$alunosCount = 0;
foreach ($cursos as $c) {
    $alunosCount += $enrollment->countStudents((int) $c['id']);
}

// Lesson model — total lessons across all courses
$lesson     = new Lesson($pdo);
$cursoIds   = array_column($cursos, 'id');
$allLessons = !empty($cursoIds) ? $lesson->findByCourses($cursoIds) : [];
$aulasCount = count($allLessons);

// Avaliações recebidas dos alunos
$evaluation    = new Evaluation($pdo);
$avaliacoes    = $evaluation->receivedByProfessor($professorId);
$mediaAvaliacoes = $evaluation->averageForProfessor($professorId);

$notaLabels = [1 => 'I', 2 => 'R', 3 => 'B', 4 => 'MB'];

// Stats array para o componente compartilhado
$stats = [
    ['label' => 'Meus Cursos',  'value' => (int) $cursosCount,   'icon' => '📚', 'color' => 'gold'],
    ['label' => 'Total Alunos', 'value' => (int) $alunosCount,   'icon' => '👥', 'color' => ''],
    ['label' => 'Minhas Aulas',  'value' => (int) $aulasCount,   'icon' => '📝', 'color' => ''],
    ['label' => 'Avaliações',   'value' => count($avaliacoes),   'icon' => '⭐', 'color' => ''],
];

$page_title = 'Painel do Professor';
require __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <!-- Stats -->
    <?php require __DIR__ . '/../includes/dashboard-stats.php'; ?>

    <!-- Lista de Cursos -->
    <div class="flex flex-between" style="align-items:center; gap:16px; margin-bottom:16px;">
        <h2 class="section-title fade-in" style="margin:0;">Meus Cursos</h2>
        <a href="<?= url('professor/create_course.php') ?>" class="btn btn-primary btn-sm">Criar novo curso</a>
    </div>

    <?php if (empty($cursos)): ?>
        <div class="card fade-in">
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <p>Voce ainda nao criou nenhum curso.</p>
                <a href="<?= url('professor/create_course.php') ?>" class="btn btn-primary">Criar meu primeiro curso</a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($cursos as $i => $curso):
            $alunosCurso = $enrollment->countStudents((int) $curso['id']);
            $aulasCurso  = $lesson->countByCourse((int) $curso['id']);
        ?>
            <div class="card fade-in" style="animation-delay: <?= $i * 0.05 ?>s;">
                <div class="flex flex-between" style="flex-wrap:wrap; gap:12px; align-items:flex-start;">
                    <div style="flex:1; min-width:200px;">
                        <h3 style="font-family:'Playfair Display',serif; font-size:1.15rem; color:var(--wine); margin-bottom:4px;">
                            <?= e($curso['nome']) ?>
                        </h3>
                        <p class="text-muted text-sm" style="margin-bottom:12px;">
                            <?= e(mb_strimwidth($curso['descricao'] ?? '', 0, 120, '...')) ?>
                        </p>
                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            <span class="badge badge-success">👥 <?= (int) $alunosCurso ?> alunos</span>
                            <span class="badge badge-info">📝 <?= (int) $aulasCurso ?> aulas</span>
                        </div>
                    </div>
                    <div class="btn-group" style="flex-shrink:0;">
                        <a href="<?= url('professor/create_lesson.php?curso_id=' . (int)$curso['id']) ?>" class="btn btn-sm btn-primary" title="Adicionar aula">Nova Aula</a>
                        <a href="<?= url('professor/edit_course.php?id=' . (int)$curso['id']) ?>" class="btn btn-sm btn-secondary" title="Editar">Editar</a>
                        <a href="<?= url('professor/manage_lessons.php?curso_id=' . (int)$curso['id']) ?>" class="btn btn-sm btn-secondary" title="Gerenciar aulas">Ver Aulas</a>
                        <a href="<?= url('professor/delete_course.php?id=' . (int)$curso['id']) ?>" class="btn btn-sm btn-danger btn-delete" title="Excluir">Excluir</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Aulas recentes -->
    <?php if (!empty($allLessons)):
        usort($allLessons, fn($a, $b) => strcmp($b['data_aula'] ?? '', $a['data_aula'] ?? ''));
        $recentLessons = array_slice($allLessons, 0, 5);
    ?>
    <h2 class="section-title fade-in" style="margin-top:32px;">Aulas Recentes</h2>
    <div class="card fade-in">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Aula</th><th>Curso</th><th>Data</th></tr></thead>
                <tbody>
                <?php foreach ($recentLessons as $rl): ?>
                    <tr>
                        <td><?= e($rl['titulo']) ?></td>
                        <td><?= e($rl['curso_nome']) ?></td>
                        <td><?= date('d/m/Y', strtotime($rl['data_aula'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Avaliações dos alunos -->
    <h2 class="section-title fade-in" style="margin-top:32px;">
        ⭐ Avaliações dos Alunos
        <?php if ($mediaAvaliacoes > 0): ?>
            <span class="text-muted text-sm" style="font-weight:normal;"> — Média: <?= e((string) $mediaAvaliacoes) ?></span>
        <?php endif; ?>
    </h2>

    <div class="card fade-in">
        <?php if (empty($avaliacoes)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <p>Nenhuma avaliação recebida ainda.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Curso</th>
                            <th>Nota</th>
                            <th>Comentário</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($avaliacoes as $av): ?>
                        <tr>
                            <td><?= e($av['aluno_nome']) ?></td>
                            <td><?= e($av['curso_nome']) ?></td>
                            <td><span class="badge badge-info"><?= e($notaLabels[(int) $av['nota']] ?? (string) $av['nota']) ?></span></td>
                            <td><?= e($av['comentario'] ?: '—') ?></td>
                            <td><?= date('d/m/Y', strtotime($av['criado_em'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
