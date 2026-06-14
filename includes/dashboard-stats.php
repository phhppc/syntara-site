<?php
/**
 * Syntara v3.0 — Template: Cards de Estatísticas do Dashboard
 *
 * Espera array $stats com ['label', 'value', 'icon', 'color']
 * Cores: gold, wine, success, info
 *
 * @package Syntara
 */


if (empty($stats)) return;
?>
<div class="stats-row">
    <?php foreach ($stats as $stat): ?>
        <?php $color = $stat['color'] ?? ''; ?>
        <div class="stat-box <?= $color ? 'stat-' . e($color) : '' ?>">
            <div class="label"><?= e($stat['label']) ?></div>
            <div class="value"><?= e((string)($stat['value'] ?? 0)) ?></div>
            <?php if (!empty($stat['icon'])): ?>
                <div style="font-size:1.2rem;margin-top:4px;opacity:0.5;"><?= $stat['icon'] ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
