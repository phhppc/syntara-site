<?php
/**
 * Syntara v3.0 — Exclusão de Usuário
 *
 * Permite ao administrador excluir um usuário e todos os dados
 * relacionados (matrículas, avaliações, feedbacks). Administradores
 * não podem ser excluídos por esta interface.
 *
 * @package Syntara
 * @version 3.0.0
 */



require __DIR__ . '/../includes/config.php';
requireLogin('admin');

$userId = inputInt('id');

if ($userId <= 0) {
    setFlash('error', 'Usuário não especificado.');
    redirect('dashboard.php');
}

if ($userId === (int) $_SESSION['user_id']) {
    setFlash('error', 'Você não pode excluir sua própria conta.');
    redirect('dashboard.php');
}

// Busca usuário via classe
$userRepo = new User($pdo);
$user = $userRepo->findById($userId);

if (!$user) {
    setFlash('error', 'Usuário não encontrado.');
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('error', 'Token de segurança inválido.');
        redirect('dashboard.php');
    }

    // Verifica se não é outro admin
    if ($user['tipo'] === 'admin') {
        setFlash('error', 'Não é possível excluir outro administrador.');
        redirect('dashboard.php');
    }

    // Cleanup via classes
    $enrollment = new Enrollment($pdo);
    $enrollment->deleteAllByStudent($userId);

    $evaluation = new Evaluation($pdo);
    $evaluation->deleteAllByStudent($userId);

    // Feedbacks: remove registros onde o usuário é aluno ou professor
    $stmt = $pdo->prepare("DELETE FROM feedbacks WHERE aluno_id = ? OR professor_id = ?");
    $stmt->execute([$userId, $userId]);

    if ($user['tipo'] === 'professor') {
        $stmt = $pdo->prepare("DELETE FROM cursos WHERE professor_id = ?");
        $stmt->execute([$userId]);
    }

    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Exclui usuário via classe
    $userRepo->delete($userId);

    setFlash('success', 'Usuário excluído com sucesso.');
    redirect('dashboard.php');
}

$page_title = 'Excluir Usuário';
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card fade-in" style="max-width:480px;margin:0 auto;text-align:center;">
        <div class="card-header" style="justify-content:center;">
            <h2>⚠️ Excluir Usuário</h2>
        </div>

        <div class="mb-2">
            <p style="font-size:1.05rem;">
                Deseja excluir <strong style="color:var(--wine);"><?= e($user['nome']) ?></strong>?
            </p>
            <p class="text-muted text-sm">
                E-mail: <?= e($user['email']) ?> &nbsp;•&nbsp; Tipo: <span class="badge badge-info"><?= e($user['tipo']) ?></span>
            </p>
            <p class="text-muted text-sm mt-1">Todos os dados relacionados (matrículas, avaliações, feedbacks) também serão removidos.</p>
        </div>

        <form method="POST" action="<?= url('admin/delete_user.php?id=' . (int)$userId) ?>">
            <?= csrfField() ?>
            <div class="form-actions" style="justify-content:center;">
                <button type="submit" class="btn btn-danger">Sim, excluir usuário</button>
                <a href="<?= url('admin/dashboard.php') ?>" class="btn btn-ghost">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
