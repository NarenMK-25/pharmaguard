<?php
// Shared settings, DB connection, session and CSRF helpers.
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'pharmaguard';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbDsn = 'mysql:host=' . $dbHost . ';port=' . $dbPort . ';dbname=' . $dbName . ';charset=utf8mb4';
$dbOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
$dbSslCa = getenv('DB_SSL_CA');
if ($dbSslCa) {
    $dbOptions[PDO::MYSQL_ATTR_SSL_CA] = $dbSslCa;
    $dbOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

try {
    $pdo = new PDO($dbDsn, $dbUser, $dbPass, $dbOptions);
} catch (PDOException $error) {
    error_log('PharmaGuard database connection failed: ' . $error->getMessage());
    http_response_code(503);
    exit('Database unavailable. Check the hosting database settings.');
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function public_demo_dashboard_enabled(): bool {
    return getenv('PUBLIC_DEMO_DASHBOARD') === 'true';
}

function require_dashboard_access(): void {
    if (empty($_SESSION['uid']) && !public_demo_dashboard_enabled()) {
        header('Location: login.php');
        exit;
    }
}

function require_login(): void {
    if (empty($_SESSION['uid'])) { header('Location: login.php'); exit; }
}
function require_admin(): void {
    require_login();
    if ($_SESSION['role'] !== 'ADMIN') { http_response_code(403); exit('Admins only.'); }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('Invalid form token. Reload the page and try again.'); }
}
