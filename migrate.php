<?php
/**
 * Syntara v3.0 — Script de Migração do Banco de Dados
 *
 * Aplica incrementalmente as alterações do schema.
 * Safe para rodar múltiplas vezes (IF EXISTS / idempotente).
 */

require __DIR__ . '/includes/config.php';

$migrations = [];
$errors = [];

// ═══════════════════════════════════════════════════════════════
// HELPER
// ═══════════════════════════════════════════════════════════════
function runMigration(PDO $pdo, string $label, string $sql, array &$migrations, array &$errors): void {
    try {
        $pdo->exec($sql);
        $migrations[] = "✅ $label";
    } catch (PDOException $e) {
        // Ignora erros de "already exists" / "duplicate key"
        if (str_contains($e->getMessage(), 'Duplicate')
            || str_contains($e->getMessage(), 'already exists')
            || str_contains($e->getMessage(), '1060')  // Duplicate column
            || str_contains($e->getMessage(), '1061')  // Duplicate key
            || str_contains($e->getMessage(), '1091')) // DROP non-existent
        {
            $migrations[] = "⏭️  $label (já existe)";
        } else {
            $errors[] = "❌ $label: " . $e->getMessage();
        }
    }
}

// ═══════════════════════════════════════════════════════════════
// MIGRATIONS v3.0
// ═══════════════════════════════════════════════════════════════

// --- Adicionar coluna updated_at / atualizado_em ---
runMigration($pdo, "usuarios.atualizado_em",
    "ALTER TABLE usuarios ADD COLUMN atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    $migrations, $errors);

runMigration($pdo, "cursos.atualizado_em",
    "ALTER TABLE cursos ADD COLUMN atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    $migrations, $errors);

runMigration($pdo, "matriculas.atualizado_em",
    "ALTER TABLE matriculas ADD COLUMN atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    $migrations, $errors);

runMigration($pdo, "aulas.atualizado_em",
    "ALTER TABLE aulas ADD COLUMN atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    $migrations, $errors);

runMigration($pdo, "avaliacoes.atualizado_em",
    "ALTER TABLE avaliacoes ADD COLUMN atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    $migrations, $errors);

runMigration($pdo, "denuncias.atualizado_em",
    "ALTER TABLE denuncias ADD COLUMN atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    $migrations, $errors);

// --- Índices compostos para dashboard (performance) ---
runMigration($pdo, "idx_usuarios_tipo_ativo",
    "ALTER TABLE usuarios ADD INDEX idx_usuarios_tipo_ativo (tipo, ativo)",
    $migrations, $errors);

runMigration($pdo, "idx_cursos_professor_ativo",
    "ALTER TABLE cursos ADD INDEX idx_cursos_professor_ativo (professor_id, ativo)",
    $migrations, $errors);

runMigration($pdo, "idx_matriculas_aluno_status",
    "ALTER TABLE matriculas ADD INDEX idx_matriculas_aluno_status (aluno_id, status)",
    $migrations, $errors);

runMigration($pdo, "idx_aulas_curso_data",
    "ALTER TABLE aulas ADD INDEX idx_aulas_curso_data (curso_id, data_aula)",
    $migrations, $errors);

runMigration($pdo, "idx_avaliacoes_professor_curso",
    "ALTER TABLE avaliacoes ADD INDEX idx_avaliacoes_professor_curso (professor_id, curso_id)",
    $migrations, $errors);

runMigration($pdo, "idx_feedbacks_aluno",
    "ALTER TABLE feedbacks ADD INDEX idx_feedbacks_aluno (aluno_id)",
    $migrations, $errors);

runMigration($pdo, "idx_feedbacks_professor",
    "ALTER TABLE feedbacks ADD INDEX idx_feedbacks_professor (professor_id)",
    $migrations, $errors);

runMigration($pdo, "idx_feedbacks_curso",
    "ALTER TABLE feedbacks ADD INDEX idx_feedbacks_curso (curso_id)",
    $migrations, $errors);

runMigration($pdo, "idx_feedbacks_tipo",
    "ALTER TABLE feedbacks ADD INDEX idx_feedbacks_tipo (tipo)",
    $migrations, $errors);

runMigration($pdo, "idx_denuncias_status_criado",
    "ALTER TABLE denuncias ADD INDEX idx_denuncias_status_criado (status, criado_em)",
    $migrations, $errors);

runMigration($pdo, "idx_denuncias_tipo",
    "ALTER TABLE denuncias ADD INDEX idx_denuncias_tipo (tipo)",
    $migrations, $errors);

runMigration($pdo, "idx_reset_user_usado",
    "ALTER TABLE password_resets ADD INDEX idx_reset_user_usado (user_id, usado)",
    $migrations, $errors);

// --- Ajustar tamanho do email para 255 ---
runMigration($pdo, "usuarios.email VARCHAR(255)",
    "ALTER TABLE usuarios MODIFY email VARCHAR(255) NOT NULL",
    $migrations, $errors);

runMigration($pdo, "cursos.nome VARCHAR(200)",
    "ALTER TABLE cursos MODIFY nome VARCHAR(200) NOT NULL",
    $migrations, $errors);

// --- Adicionar coluna tipo ao feedback (se não existir) ---
runMigration($pdo, "feedbacks.tipo",
    "ALTER TABLE feedbacks ADD COLUMN tipo ENUM('elogio','sugestao','reclamacao') NOT NULL DEFAULT 'elogio' AFTER curso_id",
    $migrations, $errors);

// --- Garantir CHECK constraints (MySQL 8.0.16+) ---
// Estes podem falhar em versões antigas do MySQL — safe para ignorar
runMigration($pdo, "chk_avaliacao_nota",
    "ALTER TABLE avaliacoes ADD CONSTRAINT chk_avaliacao_nota CHECK (nota BETWEEN 1 AND 5)",
    $migrations, $errors);

runMigration($pdo, "chk_feedback_nota",
    "ALTER TABLE feedbacks ADD CONSTRAINT chk_feedback_nota CHECK (nota BETWEEN 1 AND 5)",
    $migrations, $errors);

// ═══════════════════════════════════════════════════════════════
// OUTPUT
// ═══════════════════════════════════════════════════════════════
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migração — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body>
<header class="navbar"><div class="nav-inner">
    <a href="<?= url('index.php') ?>" class="logo"><span class="logo-icon">🎓</span> <?= SITE_NAME ?></a>
</div></header>

<div class="container" style="padding-top:24px;">
    <div class="card fade-in">
        <div class="card-header"><h2>🗄️ Migração v3.0</h2></div>

        <h3 class="section-title">Executadas</h3>
        <ul class="course-list">
            <?php foreach ($migrations as $m): ?>
                <li><?= e($m) ?></li>
            <?php endforeach; ?>
        </ul>

        <?php if (!empty($errors)): ?>
            <h3 class="section-title" style="color:var(--danger);margin-top:20px;">Erros</h3>
            <ul class="course-list">
                <?php foreach ($errors as $er): ?>
                    <li style="border-left-color:var(--danger);"><?= e($er) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="mt-3 text-center">
            <a href="<?= url('index.php') ?>" class="btn btn-primary">Voltar ao início</a>
        </div>
    </div>
</div>

<footer class="rodape" style="margin-top:auto;">
    <div class="container">
        <div class="rodape-copy"><hr><p>&copy; <?= date('Y') ?> <?= SITE_NAME ?> v<?= SITE_VERSION ?></p></div>
    </div>
</footer>
</body>
</html>
