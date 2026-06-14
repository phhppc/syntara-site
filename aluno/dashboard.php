<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('aluno');

$alunoId = userId();

/*
|--------------------------------------------------------------------------
| MATRICULAR EM CURSO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'matricular') {

    if (!verifyCsrf()) {
        setFlash('error', 'Token CSRF inválido.');
        redirect('aluno/dashboard.php');
    }

    $cursoId = inputInt('curso_id');

    if ($cursoId <= 0) {
        setFlash('error', 'Curso inválido.');
        redirect('aluno/dashboard.php');
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM matriculas
        WHERE aluno_id = ?
        AND curso_id = ?
    ");

    $stmt->execute([$alunoId, $cursoId]);

    if ($stmt->fetch()) {
        setFlash('warning', 'Você já está matriculado nesse curso.');
        redirect('aluno/dashboard.php');
    }

    $stmt = $pdo->prepare("
        INSERT INTO matriculas
        (aluno_id, curso_id, status)
        VALUES (?, ?, 'ativo')
    ");

    $stmt->execute([$alunoId, $cursoId]);

    setFlash('success', 'Matrícula realizada com sucesso!');
    redirect('aluno/dashboard.php');
}

/*
|--------------------------------------------------------------------------
| ENVIAR AVALIAÇÃO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'avaliar') {

    if (!verifyCsrf()) {
        setFlash('error', 'Token CSRF inválido.');
        redirect('aluno/dashboard.php');
    }

    $professorId = inputInt('professor_id');
    $cursoId     = inputInt('curso_id');
    $nota        = inputInt('nota');
    $comentario  = trim($_POST['comentario'] ?? '');

    if ($nota < 1 || $nota > 4) {
        setFlash('error', 'A nota deve ser entre 1 e 4.');
        redirect('aluno/dashboard.php');
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM avaliacoes
        WHERE aluno_id = ?
        AND professor_id = ?
        AND curso_id = ?
    ");

    $stmt->execute([$alunoId, $professorId, $cursoId]);

    if ($stmt->fetch()) {
        setFlash('warning', 'Você já avaliou esse professor.');
        redirect('aluno/dashboard.php');
    }

    $stmt = $pdo->prepare("
        INSERT INTO avaliacoes
        (aluno_id, professor_id, curso_id, nota, comentario)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $alunoId,
        $professorId,
        $cursoId,
        $nota,
        $comentario
    ]);

    setFlash('success', 'Avaliação enviada!');
    redirect('aluno/dashboard.php');
}

/*
|--------------------------------------------------------------------------
| CURSOS MATRICULADOS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        c.*,
        u.nome AS professor_nome
    FROM matriculas m
    INNER JOIN cursos c ON c.id = m.curso_id
    INNER JOIN usuarios u ON u.id = c.professor_id
    WHERE m.aluno_id = ?
    ORDER BY c.nome
");

$stmt->execute([$alunoId]);

$cursosMatriculados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtém a quantidade de aulas por curso matriculado
$lessonCounts = [];
if (!empty($cursosMatriculados)) {
    $cursoIds = array_column($cursosMatriculados, 'id');
    $placeholders = implode(',', array_fill(0, count($cursoIds), '?'));
    $stmt = $pdo->prepare("SELECT curso_id, COUNT(*) AS total_aulas FROM aulas WHERE curso_id IN ($placeholders) GROUP BY curso_id");
    $stmt->execute($cursoIds);
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($counts as $c) {
        $lessonCounts[$c['curso_id']] = $c['total_aulas'];
    }
}

/*
|--------------------------------------------------------------------------
| CURSOS DISPONÍVEIS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM cursos
    WHERE ativo = 1
    AND id NOT IN (
        SELECT curso_id FROM matriculas WHERE aluno_id = ?
    )
    ORDER BY nome
");

$stmt->execute([$alunoId]);

$cursosDisponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| AULAS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        a.*,
        c.nome AS curso_nome
    FROM aulas a
    INNER JOIN cursos c ON c.id = a.curso_id
    INNER JOIN matriculas m ON m.curso_id = c.id
    WHERE m.aluno_id = ?
    ORDER BY a.data_aula DESC
");

$stmt->execute([$alunoId]);

$aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| FEEDBACKS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        f.*,
        u.nome AS professor_nome,
        c.nome AS curso_nome
    FROM feedbacks f
    INNER JOIN usuarios u ON u.id = f.professor_id
    INNER JOIN cursos c ON c.id = f.curso_id
    WHERE f.aluno_id = ?
    ORDER BY f.criado_em DESC
");

$stmt->execute([$alunoId]);

$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| AVALIAÇÕES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        a.*,
        u.nome AS professor_nome,
        c.nome AS curso_nome
    FROM avaliacoes a
    INNER JOIN usuarios u ON u.id = a.professor_id
    INNER JOIN cursos c ON c.id = a.curso_id
    WHERE a.aluno_id = ?
    ORDER BY a.criado_em DESC
");

$stmt->execute([$alunoId]);

$avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| PROFESSORES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        c.id AS curso_id,
        c.nome AS curso_nome,
        u.id AS professor_id,
        u.nome AS professor_nome
    FROM matriculas m
    INNER JOIN cursos c ON c.id = m.curso_id
    INNER JOIN usuarios u ON u.id = c.professor_id
    WHERE m.aluno_id = ?
");

$stmt->execute([$alunoId]);

$professores = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| STATS
|--------------------------------------------------------------------------
*/

$totalCursos     = count($cursosMatriculados);
$totalAulas      = count($aulas);
$totalFeedbacks  = count($feedbacks);
$totalAvaliacoes = count($avaliacoes);

$page_title = 'Painel do Aluno';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <div class="stats-row">

        <div class="stat-box">
            <div class="label">Cursos</div>
            <div class="value"><?= $totalCursos ?></div>
        </div>

        <div class="stat-box">
            <div class="label">Aulas</div>
            <div class="value"><?= $totalAulas ?></div>
        </div>

        <div class="stat-box">
            <div class="label">Feedbacks</div>
            <div class="value"><?= $totalFeedbacks ?></div>
        </div>

        <div class="stat-box stat-gold">
            <div class="label">Avaliações</div>
            <div class="value"><?= $totalAvaliacoes ?></div>
        </div>

    </div>

    <!-- CURSOS -->

    <div class="card">

        <div class="card-header">
            <h3>📚 Meus Cursos</h3>
        </div>

        <?php if (empty($cursosMatriculados)): ?>

            <div class="empty-state">
                <p>Você ainda não está matriculado.</p>
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table">

                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Professor</th>
                            <th>Aulas</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($cursosMatriculados as $curso): ?>

                        <tr>
                            <td><?= e($curso['nome']) ?></td>
                            <td><?= e($curso['professor_nome']) ?></td>
                            <td><?= $lessonCounts[$curso['id']] ?? 0 ?></td>
                            <td>
    <a class="btn btn-primary btn-sm"
       href="view_lesson.php?curso_id=<?= (int)$curso['id'] ?>">
        Ver Aulas
    </a>
</td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

    <!-- MATRICULAR -->
    <?php if (!empty($cursosDisponiveis)): ?>

    <div class="card">

        <div class="card-header">
            <h3>➕ Matricular em Curso</h3>
        </div>

        <form method="POST">

            <?= csrfField() ?>
            <input type="hidden" name="action" value="matricular">

            <div class="form-group">

                <label>Curso</label>
                <select name="curso_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($cursosDisponiveis as $curso): ?>
                        <option value="<?= $curso['id'] ?>">
                            <?= e($curso['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            </div>

            <button class="btn btn-primary">Matricular</button>

        </form>

    </div>

    <?php endif; ?>

    <!-- AVALIAR PROFESSOR (NÃO FOI ALTERADO) -->

    <div class="card">

        <div class="card-header">
            <h3>⭐ Avaliar Professor</h3>
        </div>

        <?php if (empty($professores)): ?>

            <div class="empty-state">
                <p>Nenhum professor disponível.</p>
            </div>

        <?php else: ?>

        <form method="POST">

            <?= csrfField() ?>
            <input type="hidden" name="action" value="avaliar">

            <div class="form-group">

                <label>Professor / Curso</label>

                <select id="profSelect" required>
                    <option value="">Selecione</option>

                    <?php foreach ($professores as $p): ?>
                        <option value="<?= $p['professor_id'] ?>"
                                data-curso="<?= $p['curso_id'] ?>">
                            <?= e($p['professor_nome']) ?> — <?= e($p['curso_nome']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>

            </div>

            <input type="hidden" name="professor_id" id="professor_id">
            <input type="hidden" name="curso_id" id="curso_id">

            <div class="form-group">

                <label>Nota (I / R / B / MB)</label>
                <select name="nota" required>
                    <option value="">Selecione</option>
                    <option value="1">I — Insuficiente</option>
                    <option value="2">R — Regular</option>
                    <option value="3">B — Bom</option>
                    <option value="4">MB — Muito Bom</option>
                </select>

            </div>

            <div class="form-group">

                <label>Comentário</label>
                <textarea name="comentario"></textarea>

            </div>

            <button class="btn btn-primary">Enviar Avaliação</button>

        </form>

        <?php endif; ?>

    </div>

</div>

<script>
document.getElementById('profSelect')?.addEventListener('change', function () {
    const option = this.options[this.selectedIndex];
    document.getElementById('professor_id').value = option.value;
    document.getElementById('curso_id').value = option.dataset.curso;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>