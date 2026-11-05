<?php
require_once dirname(__DIR__) . '/config.php';

function api_send(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}

function api_fail(int $status, string $message): void {
    api_send(['error' => $message], $status);
    exit;
}

function api_require_method(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        header('Allow: ' . $method);
        api_fail(405, 'Method not allowed.');
    }
}

function api_require_user(): void {
    if (empty($_SESSION['uid'])) {
        api_fail(401, 'Not signed in, or session expired.');
    }
}

function api_require_admin(): void {
    if (($_SESSION['role'] ?? '') !== 'ADMIN') {
        api_fail(403, 'Only an admin can receive stock.');
    }
}

function api_check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', api_post_string('csrf'))) {
        api_fail(400, 'Invalid form token. Reload the page and try again.');
    }
}

function api_post_string(string $key): string {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}
