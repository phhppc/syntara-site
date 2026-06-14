<?php
/**
 * Syntara v3.0 — Página de Registro
 *
 * Cria novas contas de aluno ou professor com validação completa,
 * verificação de duplicidade via classe User e rate limiting.
 *
 * @package Syntara
 */



require __DIR__ . '/includes/config.php';

if (isLoggedIn()) {
    $redirect = match ($_SESSION['user_type']) {
        'admin'     => 'admin/dashboard.php',
        'professor' => 'professor/dashboard.php',
        'aluno'     => 'aluno/dashboard.php',
        default     => 'index.php',
    };
    redirect($redirect);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica CSRF
    if (!verifyCsrf()) {
        setFlash('error', 'Token CSRF inválido.');
        redirect('register.php');
    }
    if (!checkRateLimit('register', 3, 600)) {
        $error = 'Muitas tentativas. Aguarde 10 minutos.';
        saveOldInput();
    } else {
        $nome  = inputString('nome');
        $email = inputString('email');
        $senha = $_POST['senha'] ?? '';
        $tipo  = inputString('tipo');

        // Validações
        if (!$nome || !$email || !$senha || !$tipo) {
            $error = 'Preencha todos os campos obrigatórios.';
            saveOldInput();
        } elseif (!isValidName($nome)) {
            $error = 'Nome inválido. Use apenas letras, espaços, apóstrofos e hífens (2-100 caracteres).';
            saveOldInput();
        } elseif (!isValidEmail($email) || mb_strlen($email) > 255) {
            $error = 'E-mail inválido.';
            saveOldInput();
        } elseif (!isPasswordStrong($senha)) {
            $error = 'A senha deve ter no mínimo 8 caracteres, com letra maiúscula, minúscula e número.';
            saveOldInput();
        } elseif (!in_array($tipo, ['aluno', 'professor'], true)) {
            $error = 'Tipo de conta inválido.';
            saveOldInput();
        } else {
            $userRepo = new User($pdo);

            if ($userRepo->emailExists($email)) {
                $error = 'Este e-mail já está cadastrado.';
                saveOldInput();
            } else {
                $hash   = password_hash($senha, PASSWORD_DEFAULT);
                $userId = $userRepo->create($nome, $email, $hash, $tipo);

                session_regenerate_id(true);
                $_SESSION['user_id']    = $userId;
                $_SESSION['user_nome']  = $nome;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_type']  = $tipo;

                regenerateCsrf();
                resetRateLimit('register');

                $redirect = match ($tipo) {
                    'professor' => 'professor/dashboard.php',
                    'aluno'     => 'aluno/dashboard.php',
                    default     => 'index.php',
                };
                redirect($redirect);
            }
        }
    }
}

$page_title = 'Criar Conta';
require __DIR__ . '/includes/header.php';
?>

<div class="form-container">
    <div class="form-card fade-in">
        <div class="form-header">
            <h2>🎓 Criar Conta</h2>
            <p>Junte-se ao <?= SITE_NAME ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error fade-in" role="alert">
                <span class="alert-icon">❌</span>
                <span class="alert-msg"><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('register.php') ?>">
            <?= csrfField() ?>

            <?php
            $type = 'text'; $name = 'nome'; $label = 'Nome completo'; $value = oldVal('nome');
            $required = true; $attrs = 'autocomplete="name" autofocus maxlength="100"';
            require __DIR__ . '/layouts/form-field.php';
            ?>

            <?php
            $type = 'email'; $name = 'email'; $label = 'E-mail'; $value = oldVal('email');
            $required = true; $attrs = 'autocomplete="email" maxlength="255"';
            require __DIR__ . '/layouts/form-field.php';
            ?>

            <?php
            $type = 'password'; $name = 'senha'; $label = 'Senha'; $value = '';
            $required = true; $attrs = 'minlength="8"';
            require __DIR__ . '/layouts/form-field.php';
            ?>

            <?php
            $type = 'select'; $name = 'tipo'; $label = 'Tipo de conta'; $value = oldVal('tipo');
            $required = true; $options = ['aluno' => '👨‍🎓 Aluno', 'professor' => '👨‍🏫 Professor'];
            require __DIR__ . '/layouts/form-field.php';
            ?>

            <button type="submit" class="btn btn-primary btn-full btn-lg">Criar minha conta</button>
        </form>

        <div class="divider"><span>ou</span></div>

        <div class="text-center">
            <p class="text-muted text-sm">Já tem conta? <a href="<?= url('login.php') ?>" style="color:var(--gold-dark);font-weight:600;">Entrar</a></p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
