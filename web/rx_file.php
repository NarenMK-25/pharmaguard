<?php
// Serves a prescription file only to signed-in staff (uploads/ is blocked from direct web access).
require 'config.php'; require_login();
$st = $pdo->prepare('SELECT file_name, file_mime FROM prescriptions WHERE rx_id = ?');
$st->execute([(int)($_GET['id'] ?? 0)]);
$p = $st->fetch();
$path = $p ? __DIR__ . '/uploads/' . basename($p['file_name']) : '';
if (!$p || !is_file($path)) { http_response_code(404); exit('File not found.'); }
header('Content-Type: ' . $p['file_mime']);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="prescription-' . (int)$_GET['id'] . '.' . pathinfo($p['file_name'], PATHINFO_EXTENSION) . '"');
readfile($path);
