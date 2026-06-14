<?php

/**
 * Syntara v3.0 — Agenda do Aluno
 *
 * Calendário mensal com aulas dos cursos matriculados + comunicados dos professores.
 * Somente visualização (sem criar comunicados).
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('aluno');

$alunoId = (int) $_SESSION['user_id'];

// --- Mês/Ano exibido ---
$mesParam = inputInt('mes');
$anoParam = inputInt('ano');
$semanaParam = inputString('semana');
$hoje     = getdate();

$mesAtual = max(1, min(12, $mesParam ?: $hoje['mon']));
$anoAtual = max(2020, min(2099, $anoParam ?: $hoje['year']));

$mesAno = sprintf('%04d-%02d', $anoAtual, $mesAtual);

if ($semanaParam && preg_match('/^\d{4}-\d{2}-\d{2}$/', $semanaParam)) {
    $inicioSemana = $semanaParam;
} else {
    $inicioSemana = date('Y-m-d', strtotime('monday this week'));
}

$fimSemana = date('Y-m-d', strtotime($inicioSemana . ' +6 days'));

// --- Cursos matriculados ---
$stmt = $pdo->prepare(
    "SELECT c.id, c.nome, c.professor_id
     FROM matriculas m
     JOIN cursos c ON c.id = m.curso_id
     WHERE m.aluno_id = ? AND m.status = 'ativo' AND c.ativo = 1
     ORDER BY c.nome"
);
$stmt->execute([$alunoId]);
$cursos = $stmt->fetchAll();
$cursoIds = array_column($cursos, 'id');

// --- Aulas do mês nos cursos matriculados ---
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

// --- Comunicados do mês (dos cursos matriculados ou gerais) ---
$comunicadosPorDia = [];
if (!empty($cursoIds)) {
    // Comunicados específicos dos cursos + comunicados gerais (sem curso_id)
    $placeholders = implode(',', array_fill(0, count($cursoIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT co.id, co.titulo, co.mensagem, co.data_evento, co.tipo, co.curso_id,
                cu.nome AS curso_nome, u.nome AS professor_nome
         FROM comunicados co
         LEFT JOIN cursos cu ON cu.id = co.curso_id
         JOIN usuarios u ON u.id = co.professor_id
         WHERE (co.curso_id IN ($placeholders) OR co.curso_id IS NULL)
           AND co.data_evento LIKE ?
         ORDER BY co.data_evento ASC"
    );
    $params = array_merge($cursoIds, ["$mesAno-%"]);
    $stmt->execute($params);
    while ($row = $stmt->fetch()) {
        $dia = (int) substr($row['data_evento'], 8, 2);
        $comunicadosPorDia[$dia][] = $row;
    }
}

// --- Aulas da semana nos cursos matriculados ---
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

// --- Comunicados da semana (dos cursos matriculados ou gerais) ---
$comunicadosSemanaPorDia = [];
if (!empty($cursoIds)) {
    $placeholders = implode(',', array_fill(0, count($cursoIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT co.id, co.titulo, co.mensagem, co.data_evento, co.tipo, co.curso_id,
                cu.nome AS curso_nome, u.nome AS professor_nome
         FROM comunicados co
         LEFT JOIN cursos cu ON cu.id = co.curso_id
         JOIN usuarios u ON u.id = co.professor_id
         WHERE (co.curso_id IN ($placeholders) OR co.curso_id IS NULL)
           AND co.data_evento BETWEEN ? AND ?
         ORDER BY co.data_evento ASC"
    );
    $params = array_merge($cursoIds, [$inicioSemana, $fimSemana]);
    $stmt->execute($params);
    while ($row = $stmt->fetch()) {
        $row['dia_semana'] = (int) date('N', strtotime($row['data_evento']));
        $comunicadosSemanaPorDia[$row['dia_semana']][] = $row;
    }
}

// --- Calendário ---
$primeiroDia = mktime(0, 0, 0, $mesAtual, 1, $anoAtual);
$diaSemana   = (int) date('w', $primeiroDia);
$diasNoMes   = (int) date('t', $primeiroDia);

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
require __DIR__ . '/../includes/header.php';
?>

<div class="container">

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

            <?php for ($i = 0; $i < $diaSemana; $i++): ?>
                <div class="calendar-day empty"></div>
            <?php endfor; ?>

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
                <p class="text-muted text-sm">Visão de segunda a domingo com suas aulas e comunicados desta semana.</p>
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
                                </div>
                                <span><?= nl2br(e($com['mensagem'])) ?></span>
                                <?php if (!empty($com['professor_nome'])): ?><small>Por: <?= e($com['professor_nome']) ?></small><?php endif; ?>
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

    <!-- Detalhes do dia selecionado (via click) -->
    <?php
    $diaSelecionado = inputInt('dia');
    if ($diaSelecionado > 0 && $diaSelecionado <= $diasNoMes):
    ?>
    <div class="card fade-in" style="animation-delay:0.05s;" id="detalhes">
        <div class="card-header">
            <h3>📌 Detalhes — <?= $diaSelecionado ?> de <?= e($nomeMeses[$mesAtual]) ?></h3>
        </div>

        <?php if (!empty($aulasPorDia[$diaSelecionado])): ?>
            <h4 style="font-size:0.95rem; color:var(--wine); margin-bottom:8px;">Aulas</h4>
            <?php foreach ($aulasPorDia[$diaSelecionado] as $aula): ?>
                <div class="event-detail aula">
                    <strong>📝 <?= e($aula['titulo']) ?></strong>
                    <span class="text-muted text-sm" style="display:block; margin-top:4px;">
                        Curso: <?= e($aula['curso_nome'] ?? '') ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($comunicadosPorDia[$diaSelecionado])): ?>
            <h4 style="font-size:0.95rem; color:var(--wine); margin:16px 0 8px;">Comunicados</h4>
            <?php foreach ($comunicadosPorDia[$diaSelecionado] as $com): ?>
                <div class="event-detail <?= e($com['tipo']) ?>">
                    <strong><?= e($com['titulo']) ?></strong>
                    <span class="text-muted text-sm" style="display:block; margin-top:4px;">
                        Por: <?= e($com['professor_nome'] ?? '') ?><?= $com['curso_nome'] ? ' · ' . e($com['curso_nome']) : '' ?>
                    </span>
                    <p style="margin-top:6px; font-size:0.9rem;"><?= nl2br(e($com['mensagem'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (empty($aulasPorDia[$diaSelecionado]) && empty($comunicadosPorDia[$diaSelecionado])): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <p>Nenhuma aula ou comunicado para este dia.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Legenda -->
    <div class="fade-in" style="margin-top:12px; display:flex; gap:16px; flex-wrap:wrap; font-size:0.85rem; color:var(--text-muted);">
        <span>📝 Aula</span>
        <span>📢 Comunicado</span>
        <span>⚠️ Aviso</span>
        <span>🎯 Evento</span>
    </div>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
