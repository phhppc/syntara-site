<?php
/**
 * Syntara v3.0 — Template: Modal de Confirmação de Exclusão
 *
 * Gera overlay modal com formulário POST + CSRF para exclusão segura.
 * O JavaScript de main.js intercepta .btn-delete e exibe este modal.
 *
 * Variáveis esperadas:
 *   $id         ID do registro (default: 0)
 *   $action     URL do script de exclusão
 *   $cancel_url URL para voltar (default: history.back)
 *   $message    Texto de confirmação
 *
 * Uso:
 *   <?php $id = $item['id']; $action = url('delete.php'); require 'templates/confirm-delete.php'; ?>
 */


$id         = $id         ?? 0;
$action     = $action     ?? '#';
$cancel_url = $cancel_url ?? 'javascript:history.back()';
$message    = $message    ?? 'Tem certeza que deseja excluir? Esta ação não pode ser desfeita.';

$modalId = 'confirm-delete-' . $id;
?>
<div class="modal-overlay" id="<?= e($modalId ?>">
    <div class="modal-box">
        <div class="modal-icon">⚠️</div>
        <h3>Confirmar exclusão</h3>
        <p><?= e($message) ?></p>
        <div class="modal-actions">
            <form action="<?= e($action) ?>" method="POST" style="display:inline;">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= e((string)$id) ?>">
                <button type="submit" class="btn btn-danger btn-sm">Sim, excluir</button>
            </form>
            <a href="<?= e($cancel_url) ?>" class="btn btn-ghost btn-sm">Cancelar</a>
        </div>
    </div>
</div>
