<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Set these environment variables on the web host. Keep real credentials out of source control.
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3307';
$dbName = getenv('DB_NAME') ?: '';
$dbUser = getenv('DB_USER') ?: '';
$dbPassword = getenv('DB_PASSWORD') ?: '';
$studentId = getenv('STUDENT_ID') ?: 'specimen';

// A server-specific config.local.php can provide credentials when environment
// variables are unavailable. That file is excluded from version control.
$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) require $localConfig;

if (!preg_match('/^[A-Za-z0-9]+$/', $studentId)) {
    http_response_code(500);
    exit('STUDENT_ID must contain only letters and numbers.');
}

define('SPECIMEN_TABLE', $studentId . '_specimens');

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
