<?php


/**
 * Syntara v3.0 — Enviar Feedback
 *
 * Permite que um professor envie feedback para alunos matriculados em seus cursos.
 */

require __DIR__ . '/../includes/config.php';
requireLogin('professor');

$professorId = (int) $_SESSION['user_id'];
$error       = '';

// Busca alunos matriculados nos cursos do professor via Enrollment class
$enrollment   = new Enrollment($pdo);
$courseModel  = new Course($pdo);
$cursos       = $courseModel->findAll($professorId);

$alunos = [];
foreach ($cursos as $c) {
    $students = $enrollment->studentsInCourse((int) $c['id']);
    foreach ($students as $s) {
        $s['curso_nome'] = $c['nome'];
        $s['curso_id']   = $c['id'];
        $alunos[] = $s;
    }
}

if (empty($alunos)) {
    setFlash('error', 'Nenhum aluno matriculado nos seus cursos ainda.');
    redirect('professor/dashboard.php');
}

// Agrupa alunos por curso para o select
$alunosPorCurso = [];
foreach ($alunos as $a) {
    $alunosPorCurso[$a['curso_nome']][] = $a;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Token de seguranca invalido.';
        saveOldInput();
    } else {
        $alunoId  = inputInt('aluno_id');
        $cursoId  = inputInt('curso_id');
        $tipo     = inputString('tipo');
        $nota     = inputInt('nota');
        $mensagem = inputString('mensagem');

        if (!$alunoId || !$cursoId || !$tipo) {
            $error = 'Preencha todos os campos obrigatorios.';
            saveOldInput();
        } elseif (!in_array($tipo, ['elogio', 'sugestao', 'reclamacao'], true)) {
            $error = 'Tipo de feedback invalido.';
            saveOldInput();
        } elseif ($nota < 1 || $nota > 4) {
            $error = 'Nota deve ser entre 1 e 4.';
            saveOldInput();
        } elseif ($mensagem && mb_strlen($mensagem) > 1000) {
            $error = 'Mensagem muito longa (max. 1000 caracteres).';
            saveOldInput();
        } else {
            // Verifica matricula ativa
            if (!$enrollment->isActive($alunoId, $cursoId)) {
                $error = 'Aluno nao esta matriculado neste curso.';
            } else {
                $feedback = new Feedback($pdo);
                // Verifica duplicidade
                if ($feedback->exists($alunoId, $professorId, $cursoId)) {
                    $error = 'Voce ja enviou feedback para este aluno neste curso.';
                } else {
                    $feedback->create($alunoId, $professorId, $cursoId, $tipo, $nota, $mensagem);
                    setFlash('success', 'Feedback enviado com sucesso!');
                    redirect('professor/give_feedback.php');
                }
            }
        }
    }
}

$page_title = 'Enviar Feedback';
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card fade-in" style="max-width:600px;margin:0 auto;">
        <div class="card-header">
            <h2>Enviar Feedback</h2>
            <p>Avalie seus alunos</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error fade-in" role="alert">
                <span class="alert-icon">❌</span>
                <span class="alert-msg"><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('professor/give_feedback.php') ?>">
            <?= csrfField() ?>

            <?php
            // Select curso+aluno combinado
            $alunoOptions = [];
            foreach ($alunosPorCurso as $cursoNome => $cursoAlunos) {
                foreach ($cursoAlunos as $a) {
                    $alunoOptions[$a['id'] . '-' . $a['curso_id']] = $a['nome'] . ' — ' . $cursoNome;
                }
            }
            $type = 'select'; $name = 'aluno_curso'; $label = 'Aluno'; $value = oldVal('aluno_curso');
            $required = true;
            require __DIR__ . '/../layouts/form-field.php';
            ?>

            <?php
            $type = 'select'; $name = 'tipo'; $label = 'Tipo de feedback'; $value = oldVal('tipo');
            $required = true;
            $options = ['elogio' => 'Elogio', 'sugestao' => 'Sugestao', 'reclamacao' => 'Reclamacao'];
            require __DIR__ . '/../layouts/form-field.php';
            ?>

            <?php
            $type = 'select'; $name = 'nota'; $label = 'Nota (I / R / B / MB)'; $value = oldVal('nota', 4);
            $required = true;
            $options = [1 => 'I — Insuficiente', 2 => 'R — Regular', 3 => 'B — Bom', 4 => 'MB — Muito Bom'];
            require __DIR__ . '/../layouts/form-field.php';
            ?>

            <?php
            $type = 'textarea'; $name = 'mensagem'; $label = 'Mensagem (opcional)'; $value = oldVal('mensagem');
            $required = false; $attrs = 'maxlength="1000" rows="4"';
            $help = 'Maximo 1000 caracteres';
            require __DIR__ . '/../layouts/form-field.php';
            ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Enviar feedback</button>
                <a href="<?= url('professor/dashboard.php') ?>" class="btn btn-ghost">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
// Splits aluno_curso into aluno_id and curso_id
document.querySelector('form').addEventListener('submit', function(e) {
    var sel = document.querySelector('select[name="aluno_curso"]');
    var val = sel.value;
    if (val && val.indexOf('-') > -1) {
        var parts = val.split('-');
        var ai = document.createElement('input');
        ai.type = 'hidden'; ai.name = 'aluno_id'; ai.value = parts[0];
        var ci = document.createElement('input');
        ci.type = 'hidden'; ci.name = 'curso_id'; ci.value = parts[1];
        this.appendChild(ai);
        this.appendChild(ci);
        sel.disabled = true;
    }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
