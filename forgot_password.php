<?php
/**
 * Syntara v3.0 — Recuperação de Senha
 *
 * Gera token de redefinição de senha com validação de e-mail,
 * rate limiting e invalidação de tokens anteriores.
 *
 * @package Syntara
 */



require __DIR__ . '/includes/config.php';

if (isLoggedIn()) redirect('index.php');

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkRateLimit('forgot_password', 3, 600)) {
        $error = 'Muitas tentativas. Aguarde 10 minutos.';
    } else {
        $email = inputString('email');

        if (!$email || !isValidEmail($email)) {
            $error = 'Informe um e-mail válido.';
        } else {
            $userRepo = new User($pdo);
            $user     = $userRepo->findByEmail($email);

            if ($user) {
                // Invalida tokens anteriores
                $stmt = $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE user_id = ? AND usado = 0");
                $stmt->execute([$user['id']]);

                $token   = bin2hex(random_bytes(32));
                $hash    = hash('sha256', $token);
                $expires = date('Y-m-d H:i:s', time() + 3600);

                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $hash, $expires]);

                $resetLink = 'https://' . $_SERVER['HTTP_HOST'] . url('reset_password.php?token=' . urlencode($token));

                // Em produção, enviar e-mail. Aqui mostramos o link.
                $success = true;
                resetRateLimit('forgot_password');
            }

            // Sempre mostra sucesso (não revela se e-mail existe)
            $success = true;
        }
    }
}

$page_title = 'Recuperar Senha';
require __DIR__ . '/includes/header.php';
?>

<div class="form-container">
    <div class="form-card fade-in">
        <div class="form-header">
            <h2>🔑 Recuperar Senha</h2>
            <p>Enviaremos um link de redefinição</p>
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
                <span class="alert-msg">Se o e-mail existir no sistema, você receberá um link de redefinição.</span>
            </div>
            <?php if (!empty($resetLink)): ?>
                <div class="alert alert-info fade-in" role="alert" style="word-break:break-all;">
                    <span class="alert-icon">🔗</span>
                    <span class="alert-msg"><strong>Link (dev):</strong> <a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a></span>
                </div>
            <?php endif; ?>
            <div class="text-center mt-2">
                <a href="<?= url('login.php') ?>" class="btn btn-secondary">Voltar ao login</a>
            </div>
        <?php else: ?>
            <form method="POST" action="<?= url('forgot_password.php') ?>">
                <?= csrfField() ?>

                <?php
                $type = 'email'; $name = 'email'; $label = 'E-mail cadastrado'; $value = '';
                $required = true; $attrs = 'autocomplete="email" autofocus';
                require __DIR__ . '/layouts/form-field.php';
                ?>

                <button type="submit" class="btn btn-primary btn-full">Enviar link de recuperação</button>
            </form>

            <div class="divider"><span>ou</span></div>

            <div class="text-center">
                <a href="<?= url('login.php') ?>" class="btn btn-ghost btn-sm">← Voltar ao login</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
