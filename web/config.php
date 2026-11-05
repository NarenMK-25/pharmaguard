<?php
// Shared settings, DB connection, session, token + CSRF helpers.
date_default_timezone_set('Asia/Kolkata');
session_start();

// 1. UPDATE THESE 3 LINES WITH YOUR CLEVER CLOUD/REMOTE DB CREDENTIALS
const DB_DSN  = 'mysql:host=your-remote-cloud-host.com;dbname=your_remote_db_name;charset=utf8mb4';
const DB_USER = 'your_remote_user';
const DB_PASS = 'your_remote_password'; 

const TOKEN_SECRET = 'change-this-secret-key';               // must equal "secret" in servlet web.xml

// 2. UPDATE THIS LINE WITH YOUR LIVE RENDER BACKEND URL
const API_BASE = 'https://pharmaguard-tmk3.onrender.com/api';   // Tomcat servlet endpoints

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
