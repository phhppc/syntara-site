<?php
/**
 * Syntara v3.0 — Denúncia Anônima
 *
 * Permite registrar denúncias completamente anônimas, sem login,
 * sem IP, sem identificação. Gera código de acompanhamento.
 *
 * @package Syntara
 * @version 3.0.0
 */



require __DIR__ . '/includes/config.php';

// Página completamente anônima — sem login, sem IP, sem identificação
$error       = '';
$success     = false;
$codigoDenuncia = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Token de segurança inválido. Tente novamente.';
    } else {
                $descricao = inputString('descricao');

        if (!$descricao) {
            $error = 'Descreva sua denúncia.';
        } elseif (mb_strlen($descricao) > 2000) {
            $error = 'Descrição muito longa (máx. 2000 caracteres).';
        } else {
            $codigo = bin2hex(random_bytes(6));

            $stmt = $pdo->prepare("INSERT INTO denuncias (descricao, codigo) VALUES (?, ?)");
            $stmt->execute([$descricao, $codigo]);

            $codigoDenuncia = $codigo;

            // Destrói sessão se existir (garante anonimato)
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = [];
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
                }
                session_destroy();
            }

            // Evita resubmissão do formulário no F5: redireciona com o código na URL
            redirect('denuncia.php?codigo=' . urlencode($codigoDenuncia));
        }
    }
}

// Se voltamos de um redirect de sucesso, recupera o código da URL
if (!empty($_GET['codigo'])) {
    $codigoDenuncia = inputString('codigo');
    $success = true;
}

$page_title = 'Denúncia Anônima';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="card fade-in" style="max-width:600px;margin:0 auto;">
        <div class="card-header">
            <h2>🛡️ Denúncia Anônima</h2>
            <p>Sua identidade não será registrada</p>
        </div>

        <!-- Banner de anonimato -->
        <div class="anonymous-banner">
            <span class="banner-icon">🔒</span>
            <div>
                <strong>100% Anônimo</strong><br>
                <span style="opacity:0.8;font-size:0.85rem;">Não coletamos IP, dados de sessão nem nenhuma informação pessoal. Sua denúncia é completamente anônima.</span>
            </div>
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
                <span class="alert-msg"><strong>Denúncia registrada com sucesso!</strong></span>
            </div>
            <div class="card" style="background:var(--success-bg);border:2px dashed var(--success-border);text-align:center;padding:28px;">
                <p style="margin-bottom:8px;font-size:0.9rem;color:var(--success);">Guarde este código para acompanhar sua denúncia:</p>
                <div style="font-family:'Courier New',monospace;font-size:1.6rem;font-weight:700;color:var(--success);letter-spacing:4px;padding:12px 20px;background:var(--bg-card);border-radius:var(--r-sm);margin:12px 0;">
                    <?= e($codigoDenuncia) ?>
                </div>
                <p class="text-muted text-xs">Sem este código, não será possível acompanhar o status da denúncia.</p>
            </div>
            <div class="text-center mt-2">
                <a href="<?= url('index.php') ?>" class="btn btn-secondary">Voltar ao início</a>
            </div>
        <?php else: ?>
            <form method="POST" action="<?= url('denuncia.php') ?>">
                <?= csrfField() ?>

                <?php
                $type = 'textarea'; $name = 'descricao'; $label = 'Descrição da denúncia'; $value = '';
                $required = true; $attrs = 'maxlength="2000" rows="6"';
                $help = 'Descreva a situação com detalhes. Máximo 2000 caracteres. Seja objetivo para facilitar a investigação.';
                require __DIR__ . '/layouts/form-field.php';
                ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-danger">Enviar denúncia anônima</button>
                    <a href="<?= url('index.php') ?>" class="btn btn-ghost">Cancelar</a>
                </div>
            </form>

            <p class="text-muted text-xs text-center mt-2">
                🔐 Nenhum dado pessoal, IP ou cookie será armazenado junto à denúncia.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
