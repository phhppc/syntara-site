<?php
/**
 * Syntara v3.0 — Redefinição de Senha
 *
 * Permite ao usuário definir uma nova senha após validar
 * o token de recuperação recebido por e-mail.
 *
 * @package Syntara
 */

require __DIR__ . '/includes/config.php';

if (isLoggedIn()) redirect('index.php');

$error      = '';
$success    = false;
$token      = inputString('token');
$validToken = false;
$userId     = 0;

// Valida formato do token
if ($token && !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error = 'Token inválido.';
} elseif ($token) {
    $hash = hash('sha256', $token);
    $stmt = $pdo->prepare("SELECT user_id FROM password_resets WHERE token = ? AND usado = 0 AND expires_at > NOW()");
    $stmt->execute([$hash]);
    $row  = $stmt->fetch();

    if ($row) {
        $validToken = true;
        $userId     = (int) $row['user_id'];
    } else {
        $error = 'Token expirado ou inválido.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $senha  = $_POST['senha'] ?? '';
    $senha2 = $_POST['senha2'] ?? '';

    if (!$senha || !$senha2) {
        $error = 'Preencha ambos os campos.';
    } elseif ($senha !== $senha2) {
        $error = 'As senhas não coincidem.';
    } elseif (!isPasswordStrong($senha)) {
        $error = 'A senha deve ter no mínimo 8 caracteres, com letra maiúscula, minúscula e número.';
    } else {
        $hash    = password_hash($senha, PASSWORD_DEFAULT);
        $userRepo = new User($pdo);
        $userRepo->updatePassword($userId, $hash);

        // Marca token como usado
        $stmt = $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE token = ?");
        $stmt->execute([$hash]);

        $success = true;
    }
}

$page_title = 'Redefinir Senha';
require __DIR__ . '/includes/header.php';
?>

<div class="form-container">
    <div class="form-card fade-in">
        <div class="form-header">
            <h2>🔐 Nova Senha</h2>
            <p>Defina sua nova senha de acesso</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error fade-in" role="alert">
                <span class="alert-icon">❌</span>
                <span class="alert-msg"><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success fade-in" role="alert">
                <span class="alert-icon">✅</span>
                <span class="alert-msg">Senha redefinida com sucesso!</span>
            </div>
            <div class="text-center mt-2">
                <a href="<?= url('login.php') ?>" class="btn btn-primary">Entrar com nova senha</a>
            </div>
        <?php elseif ($validToken): ?>
            <form method="POST" action="<?= url('reset_password.php?token=' . urlencode($token)) ?>">
                <?= csrfField() ?>

                <?php
                $type = 'password'; $name = 'senha'; $label = 'Nova senha'; $value = '';
                $required = true; $attrs = 'minlength="8" autofocus';
                require __DIR__ . '/layouts/form-field.php';
                ?>

                <?php
                $type = 'password'; $name = 'senha2'; $label = 'Confirmar nova senha'; $value = '';
                $required = true; $attrs = 'minlength="8"';
                require __DIR__ . '/layouts/form-field.php';
                ?>

                <button type="submit" class="btn btn-primary btn-full">Redefinir senha</button>
            </form>
        <?php else: ?>
            <div class="text-center mt-2">
                <a href="<?= url('forgot_password.php') ?>" class="btn btn-secondary">Solicitar novo link</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
