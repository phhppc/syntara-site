<?php
/**
 * Syntara v3.0 — Template: Botão de Submit + Cancelamento
 *
 * Gera grupo de ações do formulário (submit + link cancelar).
 *
 * Variáveis esperadas (opcionais):
 *   $label  Texto do botão (default: 'Salvar')
 *   $cancel URL de cancelamento (opcional)
 *   $name   name do botão (default: 'submit')
 *   $class  classe CSS do botão (default: 'btn btn-primary')
 *
 * Uso:
 *   <?php $label = 'Criar curso'; $cancel = url('dashboard.php'); require 'templates/form-submit.php'; ?>
 */


$label  = $label  ?? 'Salvar';
$cancel = $cancel ?? null;
$name   = $name   ?? 'submit';
$class  = $class  ?? 'btn btn-primary';
?>
<div class="form-actions">
    <button type="submit" name="<?= e($name) ?>" class="<?= e($class) ?>">
        <?= e($label) ?>
    </button>
    <?php if ($cancel): ?>
        <a href="<?= e($cancel) ?>" class="btn btn-ghost">Cancelar</a>
    <?php endif; ?>
</div>
