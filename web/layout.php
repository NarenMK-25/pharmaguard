<?php
function page_start(string $title, string $active): void {
    $signedIn = !empty($_SESSION['uid']);
    $links = $signedIn
        ? ['dashboard' => 'Dashboard', 'medicines' => 'Medicines', 'stock' => 'Stock', 'prescriptions' => 'Prescriptions']
        : ['dashboard' => 'Dashboard'];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | PharmaGuard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body data-api="<?= e(rtrim('/' . trim(dirname($_SERVER['SCRIPT_NAME']), '/'), '/') . '/api') ?>">
<header class="bar">
  <a class="brand" href="dashboard.php">PharmaGuard</a>
  <nav>
    <?php foreach ($links as $file => $label): ?>
      <a href="<?= $file ?>.php"<?= $active === $file ? ' class="on"' : '' ?>><?= $label ?></a>
    <?php endforeach; ?>
  </nav>
  <span class="who"><?php if ($signedIn): ?>
    <?= e($_SESSION['name']) ?> (<?= e(strtolower($_SESSION['role'])) ?>) &nbsp;<a href="logout.php">Sign out</a>
  <?php else: ?>
    Public demo &nbsp;<a href="login.php">Staff sign in</a>
  <?php endif; ?></span>
</header>
<main>
<h1><?= e($title) ?></h1>
<?php }

function page_end(): void { ?>
</main>
<script src="assets/app.js"></script>
</body>
</html>
<?php }
