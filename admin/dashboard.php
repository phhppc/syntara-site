<?php
/**
 * Syntara v3.0 — Painel Administrativo
 *
 * Dashboard do administrador com estatísticas gerais, lista de usuários,
 * denúncias pendentes e visão geral dos cursos. Acesso restrito a admins.
 *
 * @package Syntara
 * @version 3.0.0
 */



require __DIR__ . '/../includes/config.php';
requireLogin('admin');

$userRepo  = new User($pdo);
$courseRepo = new Course($pdo);

// Estatísticas — classe User para contagem de usuários
$totalUsuarios    = $userRepo->countByType();
$totalProfessores = $userRepo->countByType('professor');
$totalAlunos      = $userRepo->countByType('aluno');
$totalCursos      = $courseRepo->count();
$totalAulas       = (int) $pdo->query("SELECT COUNT(*) FROM aulas")->fetchColumn();

// Denúncias pendentes
$stmt = $pdo->prepare("SELECT COUNT(*) FROM denuncias WHERE status = ?");
$stmt->execute(['pendente']);
$totalDenuncias = (int) $stmt->fetchColumn();

// Lista de usuários via classe
$usuarios = $userRepo->findAll('', 50);

// Lista de cursos com professor
$stmt = $pdo->prepare("
    SELECT c.id, c.nome, c.ativo, c.criado_em,
           u.nome as professor_nome,
           (SELECT COUNT(*) FROM matriculas WHERE curso_id = c.id AND status = ?) as total_alunos
    FROM cursos c
    JOIN usuarios u ON c.professor_id = u.id
    ORDER BY c.nome
");
$stmt->execute(['ativo']);
$cursos = $stmt->fetchAll();

// Preparar stats para o include
$stats = [
    ['label' => 'Total Usuários', 'value' => $totalUsuarios, 'icon' => '👥', 'color' => ''],
    ['label' => 'Alunos',        'value' => $totalAlunos,    'icon' => '📚', 'color' => ''],
    ['label' => 'Professores',   'value' => $totalProfessores, 'icon' => '🎓', 'color' => 'gold'],
    ['label' => 'Cursos',        'value' => $totalCursos,    'icon' => '📖', 'color' => ''],
];

if ($totalDenuncias > 0) {
    $stats[] = ['label' => 'Denúncias Pendentes', 'value' => $totalDenuncias, 'icon' => '🚨', 'color' => ''];
}

$page_title = 'Painel Administrativo';
require __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <!-- Estatísticas -->
    <h2 class="section-title">📊 Estatísticas</h2>
    <?php require __DIR__ . '/../includes/dashboard-stats.php'; ?>

    <!-- Usuários recentes -->
    <div class="card fade-in">
        <div class="card-header">
            <div>
                <h3>👥 Usuários Recentes</h3>
                <p>Últimos usuários cadastrados no sistema</p>
            </div>
            <a href="<?= url('register.php') ?>" class="btn btn-primary btn-sm">+ Novo Usuário</a>
        </div>

        <?php if (empty($usuarios)): ?>
            <div class="empty-state">
                <div class="empty-icon">👤</div>
                <p>Nenhum usuário cadastrado ainda.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th><th>Nome</th><th>E-mail</th><th>Tipo</th><th>Status</th><th>Cadastro</th><th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><?= (int)$u['id'] ?></td>
                            <td><?= e($u['nome']) ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td>
                                <?php
                                $typeLabels = ['admin' => '🛡️ Admin', 'professor' => '🎓 Professor', 'aluno' => '📚 Aluno'];
                                $typeBadges = ['admin' => 'badge-info', 'professor' => 'badge-warning', 'aluno' => 'badge-success'];
                                $badgeClass = $typeBadges[$u['tipo']] ?? 'badge-info';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $typeLabels[$u['tipo']] ?? e($u['tipo']) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $u['ativo'] ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $u['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($u['criado_em'])) ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= url('edit_user.php?id=' . (int)$u['id']) ?>" class="btn btn-sm btn-secondary" title="Editar">✏️</a>
                                    <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                        <a href="<?= url('admin/delete_user.php?id=' . (int)$u['id']) ?>" class="btn btn-sm btn-danger btn-delete" title="Excluir">🗑️</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Denúncias pendentes -->
    <?php if ($totalDenuncias > 0): ?>
    <div class="card fade-in">
        <div class="card-header">
            <div>
                <h3>🚨 Denúncias Pendentes</h3>
                <p><?= (int)$totalDenuncias ?> denúncia(s) aguardando resolução</p>
            </div>
        </div>
        <?php
        $stmt = $pdo->prepare("
            SELECT d.*, c.nome as curso_nome
            FROM denuncias d
            LEFT JOIN cursos c ON d.curso_id = c.id
            WHERE d.status = ?
            ORDER BY d.criado_em DESC
            LIMIT 10
        ");
        $stmt->execute(['pendente']);
        $denuncias = $stmt->fetchAll();
        ?>
        <?php if (empty($denuncias)): ?>
            <div class="empty-state">
                <div class="empty-icon">✅</div>
                <p>Nenhuma denúncia pendente no momento.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Código</th><th>Tipo</th><th>Mensagem</th><th>Curso</th><th>Data</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($denuncias as $d): ?>
                        <tr>
                            <td><code style="font-family:monospace; background:rgba(0,0,0,0.06); padding:2px 6px; border-radius:4px;"><?= e($d['codigo']) ?></code></td>
                            <td><?= e($d['tipo']) ?></td>
                            <td><?= e(mb_strimwidth($d['mensagem'], 0, 80, '...')) ?></td>
                            <td><?= $d['curso_id'] ? e($d['curso_nome']) : '<em style="opacity:0.5;">—</em>' ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($d['criado_em'])) ?></td>
                            <td><span class="badge badge-pending"><?= e($d['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Cursos -->
    <div class="card fade-in">
        <div class="card-header">
            <div>
                <h3>📚 Cursos do Sistema</h3>
                <p>Visão geral de todos os cursos cadastrados</p>
            </div>
        </div>
        <?php if (empty($cursos)): ?>
            <div class="empty-state">
                <div class="empty-icon">📖</div>
                <p>Nenhum curso criado ainda.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Curso</th><th>Professor</th><th>Alunos</th><th>Status</th><th>Criado em</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cursos as $c): ?>
                        <tr>
                            <td><strong><?= e($c['nome']) ?></strong></td>
                            <td><?= e($c['professor_nome']) ?></td>
                            <td><?= (int)$c['total_alunos'] ?></td>
                            <td>
                                <span class="badge <?= $c['ativo'] ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $c['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($c['criado_em'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>


<?php require __DIR__ . '/../includes/footer.php'; ?>
