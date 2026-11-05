<?php
require 'config.php'; require_login(); require 'layout.php';
$meds = $pdo->query("SELECT m.medicine_id, m.name, m.unit,
    COALESCE((SELECT SUM(b.quantity) FROM batches b WHERE b.medicine_id = m.medicine_id AND b.expiry_date >= CURDATE()),0) AS stock
    FROM medicines m ORDER BY m.name")->fetchAll();
$batches = $pdo->query("SELECT m.name, b.batch_no, b.expiry_date, b.quantity, DATEDIFF(b.expiry_date, CURDATE()) AS days_left
    FROM batches b JOIN medicines m ON m.medicine_id = b.medicine_id WHERE b.quantity > 0 ORDER BY b.expiry_date LIMIT 50")->fetchAll();
function med_options(array $meds): string {
    $o = '<option value="">Choose a medicine</option>';
    foreach ($meds as $m) $o .= '<option value="' . (int)$m['medicine_id'] . '">' . e($m['name']) . ' (' . (int)$m['stock'] . ' ' . e($m['unit']) . ' usable)</option>';
    return $o;
}
page_start('Stock', 'stock'); ?>

<div class="two-col">
  <form class="panel" data-endpoint="/dispense.php">
    <h2>Dispense</h2>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Medicine <select name="medicine_id" required><?= med_options($meds) ?></select></label>
    <label>Quantity <input type="number" name="quantity" min="1" required></label>
    <button class="btn">Dispense</button>
    <p class="note">Takes from the batch that expires first. Expired batches are never used.</p>
    <div class="msg" hidden></div>
  </form>

  <?php if ($_SESSION['role'] === 'ADMIN'): ?>
  <form class="panel" data-endpoint="/receive.php">
    <h2>Receive a batch</h2>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Medicine <select name="medicine_id" required><?= med_options($meds) ?></select></label>
    <label>Batch number <input name="batch_no" required maxlength="30"></label>
    <label>Expiry date <input type="date" name="expiry_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>"></label>
    <label>Quantity <input type="number" name="quantity" min="1" required></label>
    <label>Cost per unit (Rs.) <input type="number" name="cost_price" min="0" step="0.01" required></label>
    <button class="btn">Receive stock</button>
    <div class="msg" hidden></div>
  </form>
  <?php endif; ?>
</div>

<h2>Batches on hand (earliest expiry first)</h2>
<table><thead><tr><th>Medicine</th><th>Batch</th><th>Expires</th><th>Days left</th><th class="num">Units</th></tr></thead><tbody>
<?php foreach ($batches as $b): $d = (int)$b['days_left']; ?>
<tr><td><?= e($b['name']) ?></td><td><?= e($b['batch_no']) ?></td><td><?= e($b['expiry_date']) ?></td>
<td class="<?= $d < 0 ? 'urgent' : ($d <= 30 ? 'soon' : '') ?>"><?= $d < 0 ? 'Expired' : $d ?></td><td class="num"><?= (int)$b['quantity'] ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php page_end();
