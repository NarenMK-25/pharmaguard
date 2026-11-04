<?php
// Shared settings, DB connection, session, token + CSRF helpers.
date_default_timezone_set('Asia/Kolkata');
session_start();

const DB_DSN  = 'mysql:host=localhost;dbname=pharmaguard;charset=utf8mb4';
const DB_USER = 'root';
const DB_PASS = 'bruno';
const TOKEN_SECRET = 'change-this-secret-key';              // must equal "secret" in servlet web.xml
const API_BASE = 'http://localhost:8080/pharmaguard/api';   // Tomcat servlet endpoints

$pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function require_login(): void {
    if (empty($_SESSION['uid'])) { header('Location: login.php'); exit; }
}
function require_admin(): void {
    require_login();
    if ($_SESSION['role'] !== 'ADMIN') { http_response_code(403); exit('Admins only.'); }
}

/** Signed cookie the Java AuthFilter verifies: base64url("uid|role|expiry").HMAC-SHA256 */
function issue_token(int $uid, string $role): void {
    $exp = time() + 8 * 3600;
    $b64 = rtrim(strtr(base64_encode("$uid|$role|$exp"), '+/', '-_'), '=');
    $sig = hash_hmac('sha256', $b64, TOKEN_SECRET);
    setcookie('pg_token', "$b64.$sig", ['expires' => $exp, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('Invalid form token. Reload the page and try again.'); }
}
