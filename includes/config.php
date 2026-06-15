<?php

declare(strict_types=1);

/**
 * Syntara v3.0 — Configuração Central
 * ATUALIZADO: conexão com Aiven MySQL
 */

define('SITE_NAME', 'Syntara');
define('SITE_VERSION', '3.0.0');

define('ROOT_PATH', dirname(__DIR__));

// URL dinâmica — funciona no Railway e em qualquer host
// Detecta HTTPS corretamente no Railway (usa proxy)
if (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
) {
    $protocol = 'https';
} else {
    $protocol = 'http';
}
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('ROOT_URL', $protocol . '://' . $host);
// ─────────────────────────────────────────
// ERROS
// ─────────────────────────────────────────

error_reporting(E_ALL);
ini_set('display_errors', '0');  // desliga em produção
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
// BANCO — Aiven MySQL
// ─────────────────────────────────────────

define('DB_HOST',    'syntara-mysql-phhpxbox-7cf9.a.aivencloud.com');
define('DB_PORT',    '20075');
define('DB_NAME',    'syntara_db');
define('DB_USER',    'avnadmin');
define('DB_PASS',    'AVNS_G3ZS_OTozA_Vh-yWHAG');
define('DB_CHARSET', 'utf8mb4');
define('DB_CA',      ROOT_PATH . '/ca.pem');

$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT
     . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_SSL_CA       => DB_CA,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ]);
} catch (PDOException $e) {
    die(json_encode(['erro' => 'Erro banco: ' . $e->getMessage()]));
}

// ─────────────────────────────────────────
// AUTOLOAD CLASSES
// ─────────────────────────────────────────

spl_autoload_register(function ($class) {
    $paths = [
        ROOT_PATH . '/classes/' . $class . '.php',
        ROOT_PATH . '/models/'  . $class . '.php',
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
