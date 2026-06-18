<?php

/**
 * Syntara v3.0 — Agenda
 *
 * Calendário mensal com aulas e comunicados.
 * Professor: pode criar comunicados e ver tudo.
 * Aluno: vê aulas dos cursos matriculados + comunicados.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$tipoUsuario = $_SESSION['user_type'];
$usuarioId   = (int) $_SESSION['user_id'];

// Se aluno, redireciona para versão do aluno
if ($tipoUsuario === 'aluno') {
    require __DIR__ . '/aluno/agenda.php';
    exit;
}

// Professor e Admin podem acessar
if (!in_array($tipoUsuario, ['professor', 'admin'], true)) {
    redirect('index.php');
}



$professorId = $usuarioId;
$error   = '';
$success = '';

// --- Processar formulário de comunicado ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = inputString('action');

    if ($action === 'delete_comunicado') {
        $comunicadoId = inputInt('comunicado_id');

        if ($comunicadoId > 0) {
            $stmt = $pdo->prepare("DELETE FROM comunicados WHERE id = ? AND professor_id = ?");
            $stmt->execute([$comunicadoId, $professorId]);
            $success = 'Comunicado removido da agenda semanal.';
        }
    } elseif ($action === 'update_comunicado') {
        $comunicadoId = inputInt('comunicado_id');
        $titulo       = trim(inputString('titulo') ?? '');
        $mensagem     = trim(inputString('mensagem') ?? '');
        $dataEvento   = inputString('data_evento') ?? '';
        $comunTipo    = inputString('comun_tipo') ?? 'comunicado';
        $cursoId      = inputInt('curso_id');

        if (!$comunicadoId || !$titulo || !$mensagem || !$dataEvento) {
            $error = 'Preencha todos os campos obrigatórios para editar o comunicado.';
        } else {
            $stmt = $pdo->prepare(
                "UPDATE comunicados
                 SET titulo = ?, mensagem = ?, data_evento = ?, tipo = ?, curso_id = ?
                 WHERE id = ? AND professor_id = ?"
            );
            $stmt->execute([
                $titulo,
                $mensagem,
                $dataEvento,
                in_array($comunTipo, ['comunicado','aviso','evento'], true) ? $comunTipo : 'comunicado',
                $cursoId > 0 ? $cursoId : null,
                $comunicadoId,
                $professorId,
            ]);
            $success = 'Comunicado atualizado com sucesso!';
        }
    } else {
        $titulo     = trim(inputString('titulo') ?? '');
        $mensagem   = trim(inputString('mensagem') ?? '');
        $dataEvento = inputString('data_evento') ?? '';
        $comunTipo  = inputString('comun_tipo') ?? 'comunicado';
        $cursoId    = inputInt('curso_id');

        if (!$titulo || !$mensagem || !$dataEvento) {
            $error = 'Preencha todos os campos obrigatórios.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO comunicados (professor_id, curso_id, titulo, mensagem, data_evento, tipo)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $professorId,
                $cursoId > 0 ? $cursoId : null,
                $titulo,
                $mensagem,
                $dataEvento,
                in_array($comunTipo, ['comunicado','aviso','evento'], true) ? $comunTipo : 'comunicado',
            ]);
            $success = 'Comunicado publicado com sucesso!';
        }
    }
}

// --- Mês/Ano exibido ---
$mesParam  = inputInt('mes');
$anoParam  = inputInt('ano');
$semanaParam = inputString('semana');
$hoje      = getdate();

$mesAtual  = max(1, min(12, $mesParam ?: $hoje['mon']));
$anoAtual  = max(2020, min(2099, $anoParam ?: $hoje['year']));

$mesAno = sprintf('%04d-%02d', $anoAtual, $mesAtual); // YYYY-MM

if ($semanaParam && preg_match('/^\d{4}-\d{2}-\d{2}$/', $semanaParam)) {
    $inicioSemana = $semanaParam;
} else {
    $inicioSemana = date('Y-m-d', strtotime('monday this week'));
}

$fimSemana = date('Y-m-d', strtotime($inicioSemana . ' +6 days'));

// --- Buscar cursos do professor ---
$stmt = $pdo->prepare("SELECT id, nome FROM cursos WHERE professor_id = ? AND ativo = 1 ORDER BY nome");
$stmt->execute([$professorId]);
$cursos = $stmt->fetchAll();

// --- Buscar aulas do mês (todos os cursos do professor) ---
$cursoIds = array_column($cursos, 'id');
$aulasPorDia = [];
if (!empty($cursoIds)) {
    $placeholders = implode(',', array_fill(0, count($cursoIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT a.id, a.titulo, a.data_aula, a.curso_id, c.nome AS curso_nome
         FROM aulas a
         JOIN cursos c ON c.id = a.curso_id
         WHERE a.curso_id IN ($placeholders)
           AND a.data_aula LIKE ?
         ORDER BY a.data_aula ASC"
    );
    $params = array_merge($cursoIds, ["$mesAno-%"]);
    $stmt->execute($params);
    while ($row = $stmt->fetch()) {
        $dia = (int) substr($row['data_aula'], 8, 2);
        $aulasPorDia[$dia][] = $row;
    }
}

// --- Buscar comunicados do mês ---
$stmt = $pdo->prepare(
    "SELECT co.id, co.titulo, co.mensagem, co.data_evento, co.tipo, co.curso_id,
            cu.nome AS curso_nome
     FROM comunicados co
     LEFT JOIN cursos cu ON cu.id = co.curso_id
     WHERE co.professor_id = ?
       AND co.data_evento LIKE ?
     ORDER BY co.data_evento ASC"
);
$stmt->execute([$professorId, "$mesAno-%"]);
$comunicadosPorDia = [];
while ($row = $stmt->fetch()) {
    $dia = (int) substr($row['data_evento'], 8, 2);
    $comunicadosPorDia[$dia][] = $row;
}

// --- Comunicados da semana ---
$stmt = $pdo->prepare(
    "SELECT co.id, co.titulo, co.mensagem, co.data_evento, co.tipo, co.curso_id,
            cu.nome AS curso_nome
     FROM comunicados co
     LEFT JOIN cursos cu ON cu.id = co.curso_id
     WHERE co.professor_id = ?
       AND co.data_evento BETWEEN ? AND ?
     ORDER BY co.data_evento ASC"
);
$stmt->execute([$professorId, $inicioSemana, $fimSemana]);
$comunicadosSemana = $stmt->fetchAll();

$comunicadosSemanaPorDia = [];
foreach ($comunicadosSemana as $com) {
    $com['dia_semana'] = (int) date('N', strtotime($com['data_evento']));
    $comunicadosSemanaPorDia[$com['dia_semana']][] = $com;
}

// --- Aulas da semana ---
$aulasSemanaPorDia = [];
if (!empty($cursoIds)) {
    $placeholders = implode(',', array_fill(0, count($cursoIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT a.id, a.titulo, a.data_aula, a.curso_id, c.nome AS curso_nome
         FROM aulas a
         JOIN cursos c ON c.id = a.curso_id
         WHERE a.curso_id IN ($placeholders)
           AND a.data_aula BETWEEN ? AND ?
         ORDER BY a.data_aula ASC"
    );
    $params = array_merge($cursoIds, [$inicioSemana, $fimSemana]);
    $stmt->execute($params);
    while ($row = $stmt->fetch()) {
        $row['dia_semana'] = (int) date('N', strtotime($row['data_aula']));
        $aulasSemanaPorDia[$row['dia_semana']][] = $row;
    }
}

// --- Comunicado sendo editado ---
$editandoComunicado = null;
$editId = inputInt('editar');
if ($editId > 0) {
    $stmt = $pdo->prepare(
        "SELECT id, titulo, mensagem, data_evento, tipo, curso_id
         FROM comunicados
         WHERE id = ? AND professor_id = ?"
    );
    $stmt->execute([$editId, $professorId]);
    $editandoComunicado = $stmt->fetch() ?: null;
}

// --- Calendário ---
$primeiroDia   = mktime(0, 0, 0, $mesAtual, 1, $anoAtual);
$diaSemana     = (int) date('w', $primeiroDia); // 0=Dom, 1=Seg, ...
$diasNoMes     = (int) date('t', $primeiroDia);

// Navegação
$mesAnterior = $mesAtual - 1;
$anoAnterior = $anoAtual;
if ($mesAnterior < 1) { $mesAnterior = 12; $anoAnterior--; }

$mesSeguinte = $mesAtual + 1;
$anoSeguinte = $anoAtual;
if ($mesSeguinte > 12) { $mesSeguinte = 1; $anoSeguinte++; }

$nomeMeses = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho',
              'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

$page_title = 'Agenda';
require __DIR__ . '/includes/header.php';
?>

<div class="container">

    <!-- Mensagens -->
    <?php if ($error): ?><div class="alert alert-error fade-in"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success fade-in"><?= e($success) ?></div><?php endif; ?>

    <div class="card fade-in">
        <div class="card-header">
            <h2>📅 Agenda — <?= e($nomeMeses[$mesAtual]) ?> <?= $anoAtual ?></h2>
            <div class="flex" style="gap:8px;">
                <a class="btn btn-sm btn-secondary"
                   href="agenda.php?mes=<?= $mesAnterior ?>&ano=<?= $anoAnterior ?>">&larr; Anterior</a>
                <a class="btn btn-sm btn-secondary"
                   href="agenda.php?mes=<?= date('n') ?>&ano=<?= date('Y') ?>">Hoje</a>
                <a class="btn btn-sm btn-secondary"
                   href="agenda.php?mes=<?= $mesSeguinte ?>&ano=<?= $anoSeguinte ?>">Próximo &rarr;</a>
            </div>
        </div>

        <!-- Calendário -->
        <div class="calendar-grid">
            <div class="calendar-header">Dom</div>
            <div class="calendar-header">Seg</div>
            <div class="calendar-header">Ter</div>
            <div class="calendar-header">Qua</div>
            <div class="calendar-header">Qui</div>
            <div class="calendar-header">Sex</div>
            <div class="calendar-header">Sáb</div>

            <!-- Células vazias antes do dia 1 -->
            <?php for ($i = 0; $i < $diaSemana; $i++): ?>
                <div class="calendar-day empty"></div>
            <?php endfor; ?>

            <!-- Dias do mês -->
            <?php for ($dia = 1; $dia <= $diasNoMes; $dia++):
                $ehHoje = ($dia === $hoje['mday'] && $mesAtual === $hoje['mon'] && $anoAtual === $hoje['year']);
                $temEvento = !empty($aulasPorDia[$dia]) || !empty($comunicadosPorDia[$dia]);
            ?>
                <div class="calendar-day <?= $ehHoje ? ' today' : '' ?> <?= $temEvento ? ' has-event' : '' ?>">
                    <span class="day-number"><?= $dia ?></span>

                    <?php foreach (($aulasPorDia[$dia] ?? []) as $aula): ?>
                        <div class="calendar-event aula" title="Aula: <?= e($aula['titulo']) ?> — <?= e($aula['curso_nome'] ?? '') ?>">
                            📝 <?= e(mb_strimwidth($aula['titulo'], 0, 28, '…')) ?>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach (($comunicadosPorDia[$dia] ?? []) as $com): ?>
                        <div class="calendar-event <?= e($com['tipo']) ?>"
                             title="<?= e($com['titulo']) ?>: <?= e($com['mensagem']) ?>">
                            <?php
                            $icone = ['comunicado' => '📢', 'aviso' => '⚠️', 'evento' => '🎯'];
                            echo ($icone[$com['tipo']] ?? '📢') . ' ';
                            ?><?= e(mb_strimwidth($com['titulo'], 0, 25, '…')) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Agenda semanal -->
    <div class="card fade-in" style="animation-delay:0.05s;">
        <div class="card-header">
            <div>
                <h2>📅 Agenda Semanal</h2>
                <p class="text-muted text-sm">Visão de segunda a domingo. O aluno apenas visualiza; o professor pode editar comunicados desta semana.</p>
            </div>
            <div class="flex" style="gap:8px;">
                <a class="btn btn-sm btn-secondary" href="agenda.php?semana=<?= date('Y-m-d', strtotime($inicioSemana . ' -7 days')) ?>">&larr; Semana anterior</a>
                <a class="btn btn-sm btn-secondary" href="agenda.php?semana=<?= date('Y-m-d') ?>">Esta semana</a>
                <a class="btn btn-sm btn-secondary" href="agenda.php?semana=<?= date('Y-m-d', strtotime($inicioSemana . ' +7 days')) ?>">Próxima semana &rarr;</a>
            </div>
        </div>

        <div class="weekly-grid">
            <?php for ($diaSemana = 1; $diaSemana <= 7; $diaSemana++): ?>
                <?php
                $dataDia = date('Y-m-d', strtotime($inicioSemana . ' +' . ($diaSemana - 1) . ' days'));
                $diaLabel = date('d/m', strtotime($dataDia));
                $nomeDia = ['Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo'][$diaSemana - 1];
                $ehHojeSemana = ($dataDia === date('Y-m-d'));
                $temEventosSemana = !empty($aulasSemanaPorDia[$diaSemana]) || !empty($comunicadosSemanaPorDia[$diaSemana]);
                ?>
                <div class="weekly-day <?= $ehHojeSemana ? 'today' : '' ?> <?= $temEventosSemana ? 'has-event' : '' ?>">
                    <div class="weekly-day-header">
                        <strong><?= e($nomeDia) ?></strong>
                        <span><?= $diaLabel ?></span>
                                            </div>

                    <?php if (!empty($aulasSemanaPorDia[$diaSemana])): ?>
                        <?php foreach ($aulasSemanaPorDia[$diaSemana] as $aula): ?>
                            <div class="weekly-item aula">
                                <strong>📝 <?= e($aula['titulo']) ?></strong>
                                <span><?= e($aula['curso_nome'] ?? '') ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($comunicadosSemanaPorDia[$diaSemana])): ?>
                        <?php foreach ($comunicadosSemanaPorDia[$diaSemana] as $com): ?>
                            <div class="weekly-item <?= e($com['tipo']) ?>">
                                <div class="weekly-item-title">
                                    <span><?= ['comunicado' => '📢', 'aviso' => '⚠️', 'evento' => '🎯'][$com['tipo']] ?? '📢' ?> <?= e($com['titulo']) ?></span>
                                    <div class="weekly-item-actions">
                                        <a class="btn btn-sm btn-secondary" href="agenda.php?semana=<?= e($inicioSemana) ?>&editar=<?= (int)$com['id'] ?>">Editar</a>
                                        <form method="POST" style="display:inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete_comunicado">
                                            <input type="hidden" name="comunicado_id" value="<?= (int)$com['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remover este comunicado da agenda semanal?');">Remover</button>
                                        </form>
                                    </div>
                                </div>
                                <span><?= nl2br(e($com['mensagem'])) ?></span>
                                <?php if (!empty($com['curso_nome'])): ?><small>Curso: <?= e($com['curso_nome']) ?></small><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (empty($aulasSemanaPorDia[$diaSemana]) && empty($comunicadosSemanaPorDia[$diaSemana])): ?>
                        <div class="empty-state weekly-empty">
                            <p>Sem eventos.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <?php
    // Lista de comunicados existentes com opções de editar/remover
    if (!empty($comunicadosSemana)) {
        ?>
        <h4>Comunicados existentes</h4>
        <div class="grid" style="grid-template-columns:1fr auto; gap:8px; margin-bottom:16px;">
            <?php foreach ($comunicadosSemana as $com) { ?>
                <div><?= e($com['titulo']) ?> - <?= e($com['data_evento']) ?></div>
                <div>
                    <a class="btn btn-sm btn-secondary" href="agenda.php?semana=<?= e($inicioSemana) ?>&editar=<?= (int)$com['id'] ?>">Editar</a>
                    <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_comunicado">
                        <input type="hidden" name="comunicado_id" value="<?= (int)$com['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remover este comunicado?');">Remover</button>
                    </form>
                </div>
            <?php } ?>
        </div>
        <?php
    }
?>
<!-- Formulário de comunicado -->
    <div class="card fade-in" style="animation-delay:0.1s;">
        <div class="card-header">
            <h3>📢 <?= $editandoComunicado ? 'Editar Comunicado' : 'Novo Comunicado' ?></h3>
        </div>
        <form method="POST">
            <?= csrfField() ?>

            <?php if ($editandoComunicado): ?>
                <input type="hidden" name="action" value="update_comunicado">
                <input type="hidden" name="comunicado_id" value="<?= (int)$editandoComunicado['id'] ?>">
            <?php endif; ?>

            <div class="grid" style="grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Título *</label>
                    <input type="text" name="titulo" maxlength="200" required placeholder="Ex: Prova dia 15" value="<?= e($editandoComunicado['titulo'] ?? '') ?>">
                </div>
                <div class="form-group" style="display:flex; gap:16px;">
                    <div style="flex:1;">
                        <label>Data *</label>
                        <input type="date" name="data_evento" required value="<?= e($editandoComunicado['data_evento'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div style="flex:1;">
                        <label>Tipo</label>
                        <select name="comun_tipo">
                            <option value="comunicado" <?= ($editandoComunicado['tipo'] ?? 'comunicado') === 'comunicado' ? 'selected' : '' ?>>📢 Comunicado</option>
                            <option value="aviso" <?= ($editandoComunicado['tipo'] ?? 'comunicado') === 'aviso' ? 'selected' : '' ?>>⚠️ Aviso</option>
                            <option value="evento" <?= ($editandoComunicado['tipo'] ?? 'comunicado') === 'evento' ? 'selected' : '' ?>>🎯 Evento</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid" style="grid-template-columns:2fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Mensagem *</label>
                    <textarea name="mensagem" rows="3" required placeholder="Detalhes do comunicado..."><?= e($editandoComunicado['mensagem'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Curso (opcional)</label>
                    <select name="curso_id">
                        <option value="0" <?= (($editandoComunicado['curso_id'] ?? 0) == 0) ? 'selected' : '' ?>>Todos os cursos</option>
                        <?php foreach ($cursos as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= ((int)($editandoComunicado['curso_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flex" style="gap:8px; flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary"><?= $editandoComunicado ? 'Salvar Alterações' : 'Publicar Comunicado' ?></button>
                <?php if ($editandoComunicado): ?>
                    <a class="btn btn-sm btn-secondary" href="agenda.php?semana=<?= e($inicioSemana) ?>">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Legenda -->
    <div class="fade-in" style="margin-top:12px; display:flex; gap:16px; flex-wrap:wrap; font-size:0.85rem; color:var(--text-muted);">
        <span>📝 Aula</span>
        <span>📢 Comunicado</span>
        <span>⚠️ Aviso</span>
        <span>🎯 Evento</span>
    </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
