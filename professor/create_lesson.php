<?php


/**
 * Syntara v3.0 — Criar Aula
 *
 * Permite que um professor crie uma nova aula dentro de um de seus cursos.
 */

require __DIR__ . '/../includes/config.php';
requireLogin('professor');

$professorId = (int) $_SESSION['user_id'];
$error       = '';

$courseModel = new Course($pdo);
$cursos      = $courseModel->findAll($professorId);

if (empty($cursos)) {
    setFlash('error', 'Crie um curso antes de adicionar aulas.');
    redirect('professor/create_course.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Token de seguranca invalido.';
        saveOldInput();
    } else {
        $cursoId  = inputInt('curso_id');
        $titulo   = inputString('titulo');
        $conteudo = inputString('conteudo');
        $dataAula = inputString('data_aula');

        // Verifica ownership do curso
        $cursoCheck = $courseModel->findById($cursoId);
        if (!$cursoCheck || (int) $cursoCheck['professor_id'] !== $professorId) {
            $error = 'Curso invalido.';
            saveOldInput();
        } elseif (!$titulo) {
            $error = 'Informe o titulo da aula.';
            saveOldInput();
        } elseif (mb_strlen($titulo) > 200) {
            $error = 'Titulo muito longo (max. 200 caracteres).';
            saveOldInput();
        } elseif (mb_strlen($conteudo) > 50000) {
            $error = 'Conteudo muito longo (max. 50000 caracteres).';
            saveOldInput();
        } else {
            $lesson = new Lesson($pdo);
            $lesson->create($cursoId, $titulo, $conteudo, $dataAula ?: date('Y-m-d'));

            setFlash('success', 'Aula criada com sucesso!');
            redirect('professor/manage_lessons.php');
        }
    }
}

$page_title = 'Nova Aula';
$bodyClass  = 'professor';
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card fade-in" style="max-width:680px;margin:0 auto;">
        <div class="card-header">
            <h2>Nova Aula</h2>
            <p>Adicione conteudo a um dos seus cursos</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error fade-in" role="alert">
                <span class="alert-icon">❌</span>
                <span class="alert-msg"><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('professor/create_lesson.php') ?>">
            <?= csrfField() ?>

            <?php
            $type = 'select'; $name = 'curso_id'; $label = 'Curso'; $value = oldVal('curso_id');
            $required = true; $attrs = '';
            $options = [];
            foreach ($cursos as $c) $options[$c['id']] = $c['nome'];
            require __DIR__ . '/../layouts/form-field.php';
            ?>

            <?php
            $type = 'text'; $name = 'titulo'; $label = 'Titulo da aula'; $value = oldVal('titulo');
            $required = true; $attrs = 'autofocus maxlength="200"';
            require __DIR__ . '/../layouts/form-field.php';
            ?>

            <?php
            $type = 'textarea'; $name = 'conteudo'; $label = 'Conteudo (opcional)'; $value = oldVal('conteudo');
            $required = false; $attrs = 'maxlength="50000" rows="8"';
            require __DIR__ . '/../layouts/form-field.php';
            ?>

            <?php
            $type = 'date'; $name = 'data_aula'; $label = 'Data da aula'; $value = oldVal('data_aula', date('Y-m-d'));
            $required = false; $attrs = '';
            require __DIR__ . '/../layouts/form-field.php';
            ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Criar aula</button>
                <a href="<?= url('professor/manage_lessons.php') ?>" class="btn btn-ghost">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
