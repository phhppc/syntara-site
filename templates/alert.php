<?php
/**
 * Template: Mensagens flash (alerts)
 * Tipos: success, error, warning, info
 */
$flashTypes  = ['success', 'error', 'warning', 'info'];
$flashIcons  = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
$flashAlerts = [];

foreach ($flashTypes as $type) {
    $msg = getFlash($type);
    if ($msg) {
        $flashAlerts[] = ['type' => $type, 'msg' => $msg, 'icon' => $flashIcons[$type] ?? ''];
    }
}
?>

<?php if (!empty($flashAlerts)): ?>
    <div class="alerts-wrapper" role="alert">
        <?php foreach ($flashAlerts as $alert): ?>
            <div class="alert alert-<?= e($alert['type']) ?> fade-in">
                <span class="alert-icon"><?= $alert['icon'] ?></span>
                <span class="alert-msg"><?= e($alert['msg']) ?></span>
                <button type="button" class="alert-close" onclick="this.parentElement.classList.add('alert-hiding');setTimeout(()=>this.parentElement.remove(),400)" aria-label="Fechar">&times;</button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
