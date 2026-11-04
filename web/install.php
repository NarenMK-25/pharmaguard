<?php
// One-time setup: creates demo users. DELETE THIS FILE after running it once.
require 'config.php';
if ($pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) { exit('Users already exist. Delete install.php.'); }
$ins = $pdo->prepare('INSERT INTO users (username, full_name, password_hash, role) VALUES (?,?,?,?)');
$ins->execute(['admin', 'Store Admin', password_hash('Admin@123', PASSWORD_DEFAULT), 'ADMIN']);
$ins->execute(['pharma', 'Priya Pharmacist', password_hash('Pharma@123', PASSWORD_DEFAULT), 'PHARMACIST']);
echo 'Created admin / Admin@123 and pharma / Pharma@123. Now delete install.php.';
