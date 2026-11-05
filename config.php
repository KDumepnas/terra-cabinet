<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Set these environment variables on the web host. Keep real credentials out of source control.
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3307';
$dbName = getenv('DB_NAME') ?: '';
$dbUser = getenv('DB_USER') ?: '';
$dbPassword = getenv('DB_PASSWORD') ?: '';
$adminUsername = getenv('ADMIN_USERNAME') ?: '';
$adminPasswordHash = getenv('ADMIN_PASSWORD_HASH') ?: '';
$studentId = getenv('STUDENT_ID') ?: 'specimen';

// A server-specific config.local.php can provide credentials when environment
// variables are unavailable. That file is excluded from version control.
$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) require $localConfig;

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secureCookie = isset($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!preg_match('/^[A-Za-z0-9]+$/', $studentId)) {
    render_error_page(500, 'The catalogue table prefix is not configured correctly.');
}

define('SPECIMEN_TABLE', $studentId . '_specimens');
define('CATEGORY_TABLE', 'tc_categories');
define('SPECIMEN_CATEGORY_TABLE', 'tc_specimen_categories');

function all_categories(): array
{
    return db()->query('SELECT id, name FROM `' . CATEGORY_TABLE . '` ORDER BY name')->fetchAll();
}

function specimen_category_ids(int $specimenId): array
{
    $stmt = db()->prepare('SELECT category_id FROM `' . SPECIMEN_CATEGORY_TABLE . '` WHERE specimen_id = ?');
    $stmt->execute([$specimenId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function save_specimen_categories(int $specimenId, array $categoryIds): void
{
    $pdo = db();
    $delete = $pdo->prepare('DELETE FROM `' . SPECIMEN_CATEGORY_TABLE . '` WHERE specimen_id = ?');
    $delete->execute([$specimenId]);
    $insert = $pdo->prepare('INSERT INTO `' . SPECIMEN_CATEGORY_TABLE . '` (specimen_id, category_id) VALUES (?, ?)');
    foreach (array_unique(array_map('intval', $categoryIds)) as $categoryId) {
        $insert->execute([$specimenId, $categoryId]);
    }
}

function db(): PDO
{
    global $dbHost, $dbPort, $dbName, $dbUser, $dbPassword;
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    if ($dbName === '' || $dbUser === '') {
        throw new RuntimeException('Database settings are missing. Set DB_NAME, DB_USER, and DB_PASSWORD on the server.');
    }
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
    if ($dbPort !== '' && (int)$dbPort > 0) $dsn .= ';port=' . (int)$dbPort;
    $pdo = new PDO($dsn, $dbUser, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return isset($_SESSION['csrf_token'], $_POST['csrf']) && hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf']);
}

function is_admin(): bool
{
    global $adminUsername;
    return $adminUsername !== '' && isset($_SESSION['admin_username'])
        && hash_equals((string)$adminUsername, (string)$_SESSION['admin_username']);
}

function validated_next_path(?string $candidate): string
{
    if (!is_string($candidate) || $candidate === '' || str_contains($candidate, '\\')) return 'index.php';
    $parts = parse_url($candidate);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) return 'index.php';
    $path = $parts['path'] ?? '';
    $allowed = ['index.php', 'add-item.php', 'edit-item.php', 'manage-categories.php', 'seed.php', 'messages.php'];
    if (str_starts_with($path, '/') || str_contains($path, '..') || !in_array($path, $allowed, true)) return 'index.php';

    parse_str($parts['query'] ?? '', $query);
    $safeQuery = [];
    foreach (['id', 'slug'] as $key) {
        if (!isset($query[$key]) || !is_scalar($query[$key])) continue;
        $value = (string)$query[$key];
        if ($key === 'id' && ctype_digit($value)) $safeQuery[$key] = $value;
        if ($key === 'slug' && preg_match('/^[a-z0-9-]{1,190}$/i', $value)) $safeQuery[$key] = $value;
    }
    return $path . ($safeQuery ? '?' . http_build_query($safeQuery) : '');
}

function require_admin(): void
{
    if (is_admin()) return;
    $next = 'index.php';
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
        $candidate = $script . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');
        $next = validated_next_path($candidate);
    }
    header('Location: login.php?next=' . rawurlencode($next), true, 303);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function render_error_page(int $status, string $message): void
{
    http_response_code($status);
    $directory = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
    $prefix = basename($directory) === 'specimens' ? '../' : '';
    $title = match ($status) {
        400 => 'Request could not be processed',
        403 => 'Request denied',
        404 => 'Page not found',
        default => 'Something went wrong',
    };
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Terra Cabinet catalogue"><title>' . e($title) . ' | Terra Cabinet</title><link rel="stylesheet" href="' . e($prefix) . 'styles.css"></head><body><main class="page-content"><section class="error-panel"><p class="kicker">Terra Cabinet</p><h1>' . e($title) . '</h1><p>' . e($message) . '</p><a class="btn" href="' . e($prefix) . 'index.php">Return to the collection</a></section></main></body></html>';
    exit;
}

set_exception_handler(static function (Throwable $error): void {
    error_log('Terra Cabinet uncaught error: ' . $error);
    if (headers_sent()) exit;
    render_error_page(500, 'We could not complete that request. Please try again later.');
});
