<?php
/**
 * Syntara v3.0 — Constantes Globais
 *
 * Valores fixos do sistema. Centralizados para fácil manutenção.
 *
 * @package Syntara
 * @version 3.0.0
 */



// ══════════════════════════════════════════════════════════════
// RATE LIMITING
// ══════════════════════════════════════════════════════════════

define('RATE_LIMIT_LOGIN_MAX',        5);    // tentativas
define('RATE_LIMIT_LOGIN_WINDOW',     300);  // segundos (5 min)
define('RATE_LIMIT_REGISTER_MAX',     3);
define('RATE_LIMIT_REGISTER_WINDOW',  600);  // 10 min
define('RATE_LIMIT_FORGOT_PW_MAX',    3);
define('RATE_LIMIT_FORGOT_PW_WINDOW', 600);

// ══════════════════════════════════════════════════════════════
// SENHA
// ══════════════════════════════════════════════════════════════

define('PASSWORD_MIN_LENGTH', 8);

// ══════════════════════════════════════════════════════════════
// VALIDAÇÃO DE INPUT
// ══════════════════════════════════════════════════════════════

define('MAX_COURSE_NAME_LENGTH',     200);
define('MAX_COURSE_DESC_LENGTH',     2000);
define('MAX_LESSON_TITLE_LENGTH',    200);
define('MAX_LESSON_CONTENT_LENGTH',  50000);
define('MAX_FEEDBACK_MSG_LENGTH',    1000);
define('MAX_DENUNCIA_DESC_LENGTH',   2000);
define('MAX_NAME_LENGTH',            100);
define('MAX_EMAIL_LENGTH',           255);

// ══════════════════════════════════════════════════════════════
// PAGINAÇÃO
// ══════════════════════════════════════════════════════════════

define('DASHBOARD_USER_LIMIT', 50);

// ══════════════════════════════════════════════════════════════
// AVALIAÇÃO
// ══════════════════════════════════════════════════════════════

define('EVALUATION_MIN_SCORE', 1);
define('EVALUATION_MAX_SCORE', 5);

// ══════════════════════════════════════════════════════════════
// DENÚNCIA
// ══════════════════════════════════════════════════════════════

define('DENUNCIA_CODE_LENGTH', 12);  // bytes = 6 → hex = 12 chars

/** Tipos de denúncia válidos */
const DENUNCIA_TYPES = ['assedio', 'fraude', 'trapaca', 'discriminacao', 'outro'];
