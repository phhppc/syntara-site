<?php

/**
 * Syntara v3.0 — Funções Globais
 */

function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function csrfField(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}

function verifyCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';

    return !empty($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function regenerateCsrf(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function redirect(string $path): void
{
    $url = (
        str_starts_with($path, 'http://')
        || str_starts_with($path, 'https://')
    )
        ? $path
        : ROOT_URL . '/' . ltrim($path, '/');

    header('Location: ' . $url);
    exit;
}

function url(string $path): string {
    $base = rtrim(ROOT_URL, '/');
    return $base . '/' . ltrim($path, '/');
}

function requireLogin(?string $role = null): void
{
    if (empty($_SESSION['user_id'])) {

        setFlash('error', 'Você precisa estar logado.');

        redirect('login.php');
    }

    if ($role !== null && ($_SESSION['user_type'] ?? '') !== $role) {

        setFlash('error', 'Você não tem permissão.');

        redirect('index.php');
    }
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function userType(): ?string
{
    return $_SESSION['user_type'] ?? null;
}

function userId(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function inputInt(string $key, int $default = 0): int
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;

    return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
}

function inputString(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;

    return trim(strip_tags($value));
}

function isValidEmail(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isValidName(string $name): bool
{
    $name = trim($name);

    return strlen($name) >= 3
        && preg_match('/^[\p{L}\s]+$/u', $name);
}

function isPasswordStrong(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/\d/', $password);
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash_' . $type] = $message;
}

function getFlash(?string $type = null): ?array
{
    if ($type !== null) {

        $msg = $_SESSION['flash_' . $type] ?? null;

        unset($_SESSION['flash_' . $type]);

        return $msg
            ? ['type' => $type, 'message' => $msg]
            : null;
    }

    foreach (['success', 'error', 'warning', 'info'] as $t) {

        if (!empty($_SESSION['flash_' . $t])) {

            $msg = $_SESSION['flash_' . $t];

            unset($_SESSION['flash_' . $t]);

            return [
                'type' => $t,
                'message' => $msg
            ];
        }
    }

    return null;
}

function oldVal(string $key, string $default = ''): string
{
    return e($_SESSION['old_input'][$key] ?? $default);
}

function saveOldInput(): void
{
    $_SESSION['old_input'] = $_POST;
}

function clearOldInput(): void
{
    unset($_SESSION['old_input']);
}

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**
 * Rate Limit
 */
function checkRateLimit(string $key, int $maxAttempts = 5, int $seconds = 60): bool
{
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }

    $now = time();

    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [];
    }

    // limpa tentativas antigas
    $_SESSION['rate_limit'][$key] = array_filter(
        $_SESSION['rate_limit'][$key],
        function ($timestamp) use ($now, $seconds) {
            return ($timestamp + $seconds) > $now;
        }
    );

    // bloqueia excesso
    if (count($_SESSION['rate_limit'][$key]) >= $maxAttempts) {
        return false;
    }

    // registra tentativa
    $_SESSION['rate_limit'][$key][] = $now;

    return true;
}

/**
 * IP do usuário
 */
function clientIp(): string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }

    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Debug helper
 */
function dd(mixed ...$vars): void
{
    echo '<pre>';

    foreach ($vars as $var) {
        var_dump($var);
    }

    echo '</pre>';

    exit;
}
