<?php
/**
 * Template: Campo de formulário reutilizável
 *
 * Variáveis esperadas:
 *   $type     — input type (text, email, password, select, textarea, number, date)
 *   $name     — name do campo
 *   $label    — label visível
 *   $value    — valor atual
 *   $required — true/false
 *   $options  — array para select
 *   $help     — texto de ajuda
 *   $attrs    — atributos extras
 *   $icon     — ícone opcional (emoji)
 */
$type     = $type     ?? 'text';
$name     = $name     ?? '';
$label    = $label    ?? '';
$value    = $value    ?? '';
$required = $required ?? false;
$options  = $options  ?? [];
$help     = $help     ?? '';
$attrs    = $attrs    ?? '';
$icon     = $icon     ?? '';

$fieldId  = 'field_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
$reqMark  = $required ? '<span class="req" title="Obrigatório">*</span>' : '';
$reqAttr  = $required ? ' required aria-required="true"' : '';

$errorKey = 'error_' . $name;
$hasError = !empty($_SESSION[$errorKey]);
$errorMsg = $hasError ? $_SESSION[$errorKey] : '';
if ($hasError) unset($_SESSION[$errorKey]);
?>
<div class="form-group <?= $hasError ? 'has-error' : '' ?>">
    <?php if ($label): ?>
    <label for="<?= e($fieldId) ?>">
        <?= $icon ? '<span style="margin-right:4px">' . $icon . '</span>' : '' ?>
        <?= e($label) ?> <?= $reqMark ?>
    </label>
    <?php endif; ?>

    <?php if ($type === 'select'): ?>
        <select name="<?= e($name) ?>" id="<?= e($fieldId) ?>" <?= $reqAttr ?> <?= $attrs ?>>
            <option value="">-- Selecione --</option>
            <?php foreach ($options as $optVal => $optLabel): ?>
                <option value="<?= e((string)$optVal) ?>" <?= ((string)$value === (string)$optVal) ? 'selected' : '' ?>>
                    <?= e($optLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>

    <?php elseif ($type === 'textarea'): ?>
        <textarea
            name="<?= e($name) ?>"
            id="<?= e($fieldId) ?>"
            <?= $reqAttr ?>
            <?= $attrs ?>
            rows="5"
            placeholder="<?= e($label) ?>"
        ><?= e($value) ?></textarea>

    <?php elseif ($type === 'password'): ?>
        <div class="password-wrapper">
            <input
                type="password"
                name="<?= e($name) ?>"
                id="<?= e($fieldId) ?>"
                value=""
                <?= $reqAttr ?>
                <?= $attrs ?>
                placeholder="<?= e($label) ?>"
                autocomplete="new-password"
            >
            <button type="button" class="toggle-password" onclick="togglePassword('<?= e($fieldId) ?>')" aria-label="Mostrar senha">👁️</button>
        </div>

    <?php else: ?>
        <input
            type="<?= e($type) ?>"
            name="<?= e($name) ?>"
            id="<?= e($fieldId) ?>"
            value="<?= e($value) ?>"
            <?= $reqAttr ?>
            <?= $attrs ?>
            placeholder="<?= e($label) ?>"
        >
    <?php endif; ?>

    <?php if ($help): ?>
        <small class="form-help"><?= e($help) ?></small>
    <?php endif; ?>

    <?php if ($hasError): ?>
        <small class="form-error">⚠️ <?= e($errorMsg) ?></small>
    <?php endif; ?>
</div>
