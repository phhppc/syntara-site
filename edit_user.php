<?php
/**
 * Syntara v3.0 — Edição de Perfil
 *
 * Permite ao usuário editar nome, e-mail e senha.
 * Administradores podem editar outros usuários (exceto outros admins).
 *
 * @package Syntara
 */



require __DIR__ . '/includes/config.php';
requireLogin();

$userId   = (int) $_SESSION['user_id'];
$userType = $_SESSION['user_type'];
$error    = '';

// Admin pode editar outros usuários
$editId = $userId;
if ($userType === 'admin' && isset($_GET['id'])) {
    $editId = inputInt('id');
    if ($editId <= 0) {
        setFlash('error', 'Usuário não especificado.');
        redirect('admin/dashboard.php');
    }
    // Admin não edita outros admins
    $userRepo = new User($pdo);
    $target   = $userRepo->findById($editId);
    if ($target && $target['tipo'] === 'admin' && $editId !== $userId) {
        setFlash('error', 'Você não pode editar outro administrador.');
        redirect('admin/dashboard.php');
    }
}

// Busca dados
$userRepo = new User($pdo);
$user     = $userRepo->findById($editId);

if (!$user) {
    setFlash('error', 'Usuário não encontrado.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = inputString('nome');
    $email = inputString('email');
    $senha = $_POST['senha'] ?? '';

    if (!$nome || !$email) {
        $error = 'Preencha nome e e-mail.';
        saveOldInput();
    } elseif (!isValidName($nome)) {
        $error = 'Nome inválido.';
        saveOldInput();
    } elseif (!isValidEmail($email)) {
        $error = 'E-mail inválido.';
        saveOldInput();
    } else {
        // Verifica duplicidade de email
        if ($userRepo->emailExists($email, $editId)) {
            $error = 'Este e-mail já está em uso.';
            saveOldInput();
        } else {
            if ($senha) {
                if (!isPasswordStrong($senha)) {
                    $error = 'A senha deve ter no mínimo 8 caracteres, com letra maiúscula, minúscula e número.';
                    saveOldInput();
                } else {
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    $userRepo->updateProfile($editId, $nome, $email);
                    $userRepo->updatePassword($editId, $hash);
                }
            } else {
                $userRepo->updateProfile($editId, $nome, $email);
            }

            if (!$error) {
                // Atualiza sessão se editando a si mesmo
                if ($editId === $userId) {
                    $_SESSION['user_nome']  = $nome;
                    $_SESSION['user_email'] = $email;
                    session_regenerate_id(true);
                    regenerateCsrf();
                }

                setFlash('success', 'Perfil atualizado com sucesso!');
                redirect($userType === 'admin' && $editId !== $userId ? 'admin/dashboard.php' : 'edit_user.php');
            }
        }
    }
}

$page_title = 'Editar Perfil';
require __DIR__ . '/includes/header.php';
?>

<div class="form-container">
    <div class="form-card fade-in" style="max-width:520px;">
        <div class="form-header">
            <h2>👤 Editar Perfil</h2>
            <p>Atualize seus dados pessoais</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error fade-in" role="alert">
                <span class="alert-icon">❌</span>
                <span class="alert-msg"><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('edit_user.php') . ($editId !== $userId ? '?id=' . (int)$editId : '') ?>">
            <?= csrfField() ?>

            <?php
            $type = 'text'; $name = 'nome'; $label = 'Nome completo'; $value = oldVal('nome', $user['nome']);
            $required = true; $attrs = 'autocomplete="name" maxlength="100"';
            require __DIR__ . '/layouts/form-field.php';
            ?>

            <?php
            $type = 'email'; $name = 'email'; $label = 'E-mail'; $value = oldVal('email', $user['email']);
            $required = true; $attrs = 'autocomplete="email" maxlength="255"';
            require __DIR__ . '/layouts/form-field.php';
            ?>

            <?php
            $type = 'password'; $name = 'senha'; $label = 'Nova senha (deixe vazio para manter)'; $value = '';
            $required = false; $attrs = 'minlength="8"';
            $help = 'Mínimo 8 caracteres, com letra maiúscula, minúscula e número';
            require __DIR__ . '/layouts/form-field.php';
            ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salvar alterações</button>
                <a href="<?= url($userType === 'admin' ? 'admin/dashboard.php' : ($userType === 'professor' ? 'professor/dashboard.php' : 'aluno/dashboard.php')) ?>" class="btn btn-ghost">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
