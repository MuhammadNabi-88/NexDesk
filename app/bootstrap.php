<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $file = ROOT . '/app/' . $class . '.php';
    if (is_file($file)) {
        require $file;
    }
});

function config(string $path, mixed $default = null): mixed
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require ROOT . '/config.php';
        if (is_file(ROOT . '/config.local.php')) {
            $cfg = array_replace_recursive($cfg, require ROOT . '/config.local.php');
        }
    }
    $value = $cfg;
    foreach (explode('.', $path) as $key) {
        if (!is_array($value) || !array_key_exists($key, $value)) {
            return $default;
        }
        $value = $value[$key];
    }
    return $value;
}

date_default_timezone_set((string) config('timezone', 'UTC'));

// Works in the web root and in a sub-folder: the base path is the script folder relative to the document root.
$docRoot = rtrim(str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$scriptFile = str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
$baseDir = ($docRoot !== '' && str_starts_with($scriptFile, $docRoot)) ? dirname(substr($scriptFile, strlen($docRoot))) : '/';
define('BASE_URL', ($baseDir === '/' || $baseDir === '.' || $baseDir === '\\') ? '' : rtrim($baseDir, '/'));

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:; script-src 'self'; frame-ancestors 'none'");

/* ---------- helpers ---------- */

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['old'][$key] ?? $default);
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = (string) ($_POST['_csrf'] ?? '');
    if (!hash_equals(csrf_token(), $sent)) {
        abort(419, 'Your session expired. Go back, reload the page and try again.');
    }
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function abort(int $code, string $message = ''): never
{
    http_response_code($code);
    $titles = [403 => 'No access', 404 => 'Page not found', 419 => 'Session expired'];
    view('error', ['title' => $titles[$code] ?? 'Error', 'code' => $code, 'message' => $message], Auth::check() ? 'app' : 'guest');
    exit;
}

function view(string $name, array $data = [], string $layout = 'app'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require ROOT . "/views/$name.php";
    $content = ob_get_clean();
    require ROOT . "/views/layout-$layout.php";
    unset($_SESSION['old']);
}

function time_ago(string $timestamp): string
{
    $diff = max(0, time() - strtotime($timestamp));
    return match (true) {
        $diff < 60 => 'just now',
        $diff < 3600 => intdiv($diff, 60) . ' min ago',
        $diff < 86400 => intdiv($diff, 3600) . ' h ago',
        $diff < 604800 => intdiv($diff, 86400) . ' d ago',
        default => date('d M Y', strtotime($timestamp)),
    };
}

/** @return array{0: string, 1: string} label and css class for the SLA chip */
function sla_label(array $ticket): array
{
    if (!in_array($ticket['status'], Tickets::ACTIVE, true)) {
        return ['Completed', 'ok'];
    }
    $diff = strtotime($ticket['due_at']) - time();
    $abs = abs($diff);
    $text = intdiv($abs, 3600) > 0 ? intdiv($abs, 3600) . 'h ' . intdiv($abs % 3600, 60) . 'm' : intdiv($abs, 60) . 'm';
    if ($diff < 0) {
        return ["Overdue $text", 'bad'];
    }
    return ["Due in $text", $diff < 7200 ? 'warn' : 'ok'];
}

function status_pill(string $status): string
{
    return '<span class="pill s-' . e($status) . '">' . e(Tickets::STATUSES[$status] ?? $status) . '</span>';
}

function priority_pill(string $priority): string
{
    return '<span class="pill p-' . e($priority) . '">' . e(Tickets::PRIORITIES[$priority] ?? $priority) . '</span>';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    return strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
}
