<?php

declare(strict_types=1);

/**
 * Syntara v3.0 — Configuração Central
 */

define('SITE_NAME', 'Syntara');
define('SITE_VERSION', '3.0.0');

define('ROOT_PATH', dirname(__DIR__));
define('ROOT_URL', 'http://localhost/syntara');

// ─────────────────────────────────────────
// ERROS
// ─────────────────────────────────────────

error_reporting(E_ALL);

ini_set('display_errors', '1');
ini_set('log_errors', '1');

if (!is_dir(ROOT_PATH . '/app_logs')) {
    mkdir(ROOT_PATH . '/app_logs', 0777, true);
}

ini_set('error_log', ROOT_PATH . '/app_logs/php_errors.log');

// ─────────────────────────────────────────
// HEADERS
// ─────────────────────────────────────────

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
}

// ─────────────────────────────────────────
// SESSÃO
// ─────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {

    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');

    session_start();
}

// ─────────────────────────────────────────
// BANCO
// ─────────────────────────────────────────

define('DB_HOST', 'localhost');
define('DB_NAME', 'syntara_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

try {

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

} catch (PDOException $e) {

    die("Erro banco: " . $e->getMessage());
}

// ─────────────────────────────────────────
// AUTOLOAD CLASSES
// ─────────────────────────────────────────

spl_autoload_register(function ($class) {

    $paths = [
        ROOT_PATH . '/classes/' . $class . '.php',
        ROOT_PATH . '/models/' . $class . '.php',
    ];

    foreach ($paths as $file) {

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ─────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────

require_once ROOT_PATH . '/includes/functions.php';