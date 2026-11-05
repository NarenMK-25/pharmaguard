<?php
require 'config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([trim($_POST['username'] ?? '')]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['user_id'];
        $_SESSION['name'] = $u['full_name'];
        $_SESSION['role'] = $u['role'];
        header('Location: dashboard.php'); exit;
    }
    $error = 'Username or password is incorrect.';
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in | PharmaGuard</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css"></head>
<body class="login">
<form method="post" class="login-box">
  <h1>PharmaGuard</h1>
  <p class="sub">Know what expires before it does.</p>
  <?php if ($error): ?><div class="msg err"><?= e($error) ?></div><?php endif; ?>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label>Username <input name="username" required autofocus></label>
  <label>Password <input type="password" name="password" required></label>
  <button class="btn">Sign in</button>
</form>
</body></html>
