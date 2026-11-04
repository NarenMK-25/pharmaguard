<?php
require 'config.php'; require_login(); require 'layout.php';
$msg = ''; $isErr = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'upload') {
        $f = $_FILES['rx'] ?? null;
        $types = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
        $patient = trim($_POST['patient_name'] ?? ''); $doctor = trim($_POST['doctor_name'] ?? '');
        if ($patient === '' || $doctor === '') { $msg = 'Enter the patient and doctor names.'; $isErr = true; }
        elseif (!$f || $f['error'] !== UPLOAD_ERR_OK) { $msg = 'Choose a file to upload.'; $isErr = true; }
        elseif ($f['size'] > 2 * 1024 * 1024) { $msg = 'File is larger than 2 MB.'; $isErr = true; }
        else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);   // real type, not the browser's claim
            if (!isset($types[$mime])) { $msg = 'Only PDF, JPG or PNG files are accepted.'; $isErr = true; }
            else {
                $name = bin2hex(random_bytes(16)) . '.' . $types[$mime];
                move_uploaded_file($f['tmp_name'], __DIR__ . '/uploads/' . $name);
                $pdo->prepare('INSERT INTO prescriptions (patient_name, doctor_name, file_name, file_mime, uploaded_by) VALUES (?,?,?,?,?)')
                    ->execute([$patient, $doctor, $name, $mime, $_SESSION['uid']]);
                $msg = 'Prescription uploaded.';
            }
        }
    } elseif (($_POST['action'] ?? '') === 'dispensed') {
        $pdo->prepare("UPDATE prescriptions SET status = 'DISPENSED' WHERE rx_id = ? AND status = 'PENDING'")->execute([(int)$_POST['rx_id']]);
        $msg = 'Marked as dispensed.';
    }
}
$list = $pdo->query('SELECT p.*, u.full_name FROM prescriptions p LEFT JOIN users u ON u.user_id = p.uploaded_by ORDER BY p.created_at DESC LIMIT 100')->fetchAll();
page_start('Prescriptions', 'prescriptions'); ?>
<?php if ($msg): ?><div class="msg <?= $isErr ? 'err' : 'ok' ?>"><?= e($msg) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="panel narrow">
  <h2>Upload a prescription</h2>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="action" value="upload">
  <label>Patient name <input name="patient_name" required maxlength="80"></label>
  <label>Doctor name <input name="doctor_name" required maxlength="80"></label>
  <label>File (PDF, JPG or PNG, up to 2 MB) <input type="file" name="rx" accept=".pdf,.jpg,.jpeg,.png" required></label>
  <button class="btn">Upload</button>
</form>

<h2>Recent prescriptions</h2>
<table><thead><tr><th>Patient</th><th>Doctor</th><th>Uploaded by</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>
<?php if (!$list): ?><tr><td colspan="6" class="empty">No prescriptions yet. Upload one above.</td></tr><?php endif; ?>
<?php foreach ($list as $p): ?>
<tr><td><?= e($p['patient_name']) ?></td><td><?= e($p['doctor_name']) ?></td><td><?= e($p['full_name']) ?></td><td><?= e($p['created_at']) ?></td>
<td><span class="pill <?= strtolower($p['status']) ?>"><?= e(ucfirst(strtolower($p['status']))) ?></span></td>
<td class="actions"><a href="rx_file.php?id=<?= (int)$p['rx_id'] ?>" target="_blank" rel="noopener">View</a>
<?php if ($p['status'] === 'PENDING'): ?>
<form method="post" class="inline"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="dispensed">
<input type="hidden" name="rx_id" value="<?= (int)$p['rx_id'] ?>"><button class="link">Mark dispensed</button></form>
<?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php page_end();
