<?php
require 'config.php'; require_login(); require 'layout.php';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin(); csrf_check();
    $name = trim($_POST['name'] ?? ''); $reorder = (int)($_POST['reorder_level'] ?? 0);
    if ($name === '' || $reorder < 1) { $msg = 'Enter a name and a reorder level of at least 1.'; }
    else {
        $pdo->prepare('INSERT INTO medicines (name, generic_name, category, unit, reorder_level, supplier_id) VALUES (?,?,?,?,?,?)')
            ->execute([$name, trim($_POST['generic_name'] ?? ''), trim($_POST['category'] ?? ''), trim($_POST['unit'] ?? 'tablet'),
                       $reorder, ($_POST['supplier_id'] ?? '') !== '' ? (int)$_POST['supplier_id'] : null]);
        $msg = 'Medicine added.';
    }
}
$suppliers = $pdo->query('SELECT supplier_id, name FROM suppliers ORDER BY name')->fetchAll();
$meds = $pdo->query("SELECT m.*, s.name AS supplier,
    COALESCE((SELECT SUM(b.quantity) FROM batches b WHERE b.medicine_id = m.medicine_id AND b.expiry_date >= CURDATE()),0) AS stock
    FROM medicines m LEFT JOIN suppliers s ON s.supplier_id = m.supplier_id ORDER BY m.name")->fetchAll();
page_start('Medicines', 'medicines'); ?>
<?php if ($msg): ?><div class="msg"><?= e($msg) ?></div><?php endif; ?>

<table>
<thead><tr><th>Name</th><th>Category</th><th>Supplier</th><th class="num">Usable stock</th><th class="num">Reorder level</th></tr></thead>
<tbody>
<?php foreach ($meds as $m): $low = $m['stock'] <= $m['reorder_level']; ?>
<tr><td><?= e($m['name']) ?><small><?= e($m['generic_name']) ?></small></td><td><?= e($m['category']) ?></td><td><?= e($m['supplier']) ?></td>
<td class="num <?= $low ? 'urgent' : '' ?>"><?= (int)$m['stock'] ?> <?= e($m['unit']) ?></td><td class="num"><?= (int)$m['reorder_level'] ?></td></tr>
<?php endforeach; ?>
</tbody></table>

<?php if ($_SESSION['role'] === 'ADMIN'): ?>
<h2>Add a medicine</h2>
<form method="post" class="grid-form">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label>Brand name <input name="name" required maxlength="80"></label>
  <label>Generic name <input name="generic_name" maxlength="80"></label>
  <label>Category <input name="category" maxlength="40"></label>
  <label>Unit <input name="unit" value="tablet" maxlength="15"></label>
  <label>Reorder level <input type="number" name="reorder_level" min="1" value="50" required></label>
  <label>Supplier <select name="supplier_id"><option value="">None</option>
    <?php foreach ($suppliers as $s): ?><option value="<?= (int)$s['supplier_id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?></select></label>
  <button class="btn">Add medicine</button>
</form>
<?php endif; ?>
<?php page_end();
