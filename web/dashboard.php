<?php
require 'config.php'; require_dashboard_access(); require 'layout.php';
page_start('Today at the pharmacy', 'dashboard'); ?>
<?php if (empty($_SESSION['uid'])): ?>
<div class="msg">Public demo dashboard · Read-only · Sample data only. Do not use for real pharmacy or patient data.</div>
<?php endif; ?>
<div id="alert-error" class="msg err" hidden></div>

<section class="cards">
  <div class="card warn"><b id="c-exp">-</b><span>batches expire within 30 days</span></div>
  <div class="card bad"><b id="c-expired">-</b><span>expired batches still on the shelf</span></div>
  <div class="card low"><b id="c-low">-</b><span>medicines at or below reorder level</span></div>
</section>

<h2>Expiring within 30 days</h2>
<table id="t-exp"><thead><tr><th>Medicine</th><th>Batch</th><th>Expires</th><th>Days left</th><th class="num">Units</th></tr></thead><tbody></tbody></table>

<h2>Expired: remove from shelf</h2>
<table id="t-expired"><thead><tr><th>Medicine</th><th>Batch</th><th>Expired on</th><th>Days ago</th><th class="num">Units</th></tr></thead><tbody></tbody></table>

<div class="row-head"><h2>Low stock and suggested reorders</h2><button class="btn ghost" id="csv-btn" type="button">Download reorder list (CSV)</button></div>
<table id="t-low"><thead><tr><th>Medicine</th><th>Supplier</th><th class="num">In stock</th><th class="num">Reorder level</th><th class="num">Used (30 days)</th><th class="num">Order</th></tr></thead><tbody></tbody></table>
<p class="note">Order = the larger of 2 x reorder level or last 30 days' usage, minus unexpired stock on hand.</p>
<?php page_end();
