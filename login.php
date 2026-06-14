<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Redireciona usuário já autenticado para o dashboard correto
if (isLoggedIn()) {
    $type = $_SESSION['user_type'] ?? '';
    switch ($type) {
        case 'admin':
            redirect('admin/dashboard.php');
            break;
        case 'professor':
            redirect('professor/dashboard.php');
            break;
        case 'aluno':
            redirect('aluno/dashboard.php');
            break;
        default:
            redirect('index.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf()) {
        setFlash('error', 'Token CSRF inválido.');
        redirect('login.php');
    }

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        setFlash('error', 'Preencha todos os campos.');
        redirect('login.php');
    }

    try {

        $stmt = $pdo->prepare("
            SELECT *
            FROM usuarios
            WHERE email = ?
            AND ativo = 1
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($senha, $user['senha'])) {

            setFlash('error', 'Email ou senha inválidos.');
            redirect('login.php');
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = $user['tipo'];

        setFlash('success', 'Login realizado com sucesso!');

        switch ($user['tipo']) {

            case 'admin':
                redirect('admin/dashboard.php');
                break;

            case 'professor':
                redirect('professor/dashboard.php');
                break;

            default:
                redirect('aluno/dashboard.php');
                break;
        }

    } catch (PDOException $e) {

        die('Erro no login: ' . $e->getMessage());
    }
}

$page_title = 'Login';

require_once __DIR__ . '/includes/header.php';
?>

<div class="form-container">
    <div class="form-card">

        <div class="form-header">
            <h2>Entrar</h2>
            <p>Acesse sua conta no Syntara</p>
        </div>

        <form method="POST">

            <?= csrfField() ?>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Senha</label>

                <div class="password-wrapper">
                    <input type="password" name="senha" id="senha" required>

                    <button type="button"
                            class="toggle-password"
                            onclick="togglePassword('senha')">
                        👁️
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Entrar
            </button>
            <div class="text-center" style="margin-top:10px;">
                <a href="forgot_password.php" class="link">Esqueceu sua senha?</a>
            </div>

        </form>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>