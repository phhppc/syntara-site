<?php
/**
 * Migration — Cria tabela comunicados
 * Acesse: http://localhost/syntara/migrate_comunicados.php
 * DELETE este arquivo após executar!
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/config.php';

if (!isLoggedIn() || ($_SESSION['user_type'] ?? '') !== 'admin') {
    die('Acesso negado. Faça login como admin.');
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS comunicados (
        id INT PRIMARY KEY AUTO_INCREMENT,
        professor_id INT NOT NULL,
        curso_id INT DEFAULT NULL,
        titulo VARCHAR(200) NOT NULL,
        mensagem TEXT NOT NULL,
        data_evento DATE NOT NULL,
        tipo ENUM('comunicado','aviso','evento') NOT NULL DEFAULT 'comunicado',
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE SET NULL ON UPDATE CASCADE,
        INDEX idx_comunicados_professor (professor_id),
        INDEX idx_comunicados_curso (curso_id),
        INDEX idx_comunicados_data (data_evento),
        INDEX idx_comunicados_tipo (tipo),
        INDEX idx_comunicados_data_tipo (data_evento, tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comunicados e eventos da agenda dos professores'");

    echo "✅ Tabela 'comunicados' criada com sucesso!\n";

    // Verificar se já existe
    $stmt = $pdo->query("SELECT COUNT(*) FROM comunicados");
    $count = $stmt->fetchColumn();
    echo "📊 Registros existentes: $count\n";

} catch (Throwable $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
