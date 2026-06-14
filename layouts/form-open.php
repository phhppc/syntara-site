<?php
/**
 * Syntara v3.0 — Template: Abertura de Formulário
 *
 * Gera <form> com CSRF automático e atributos configuráveis.
 *
 * Variáveis esperadas (opcionais):
 *   $form_action  URL de action (default: URI atual)
 *   $form_method  POST ou GET (default: POST)
 *   $form_id      id do form
 *   $form_class   classes CSS adicionais
 *
 * Uso:
 *   <?php $form_action = url('save.php'); require 'layouts/form-open.php'; ?>
 */


$form_action = $form_action ?? $_SERVER['REQUEST_URI'];
$form_method = $form_method ?? 'POST';
$form_id     = $form_id     ?? '';
$form_class  = $form_class  ?? '';

$attrs = '';
if ($form_id)    $attrs .= ' id="' . e($form_id) . '"';
if ($form_class) $attrs .= ' class="' . e($form_class) . '"';
?>
<form action="<?= e($form_action) ?>" method="<?= e($form_method) ?>"<?= $attrs ?> autocomplete="off">
<?= csrfField() ?>
