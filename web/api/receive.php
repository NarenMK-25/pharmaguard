<?php
require __DIR__ . '/_bootstrap.php';
api_require_method('POST');
api_require_user();
api_require_admin();
api_check_csrf();

$medicineId = filter_var($_POST['medicine_id'] ?? null, FILTER_VALIDATE_INT);
$quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
$batchNo = trim(api_post_string('batch_no'));
$expiryDate = api_post_string('expiry_date');
$costPrice = api_post_string('cost_price');
$expiry = DateTimeImmutable::createFromFormat('!Y-m-d', $expiryDate);

if (!$medicineId || !$quantity || $quantity < 1 || $batchNo === ''
    || strlen($batchNo) > 30 || !$expiry || $expiry->format('Y-m-d') !== $expiryDate
    || $expiry <= new DateTimeImmutable('today') || !is_numeric($costPrice)
    || !is_finite((float)$costPrice) || (float)$costPrice < 0) {
    api_fail(400, 'Check the form: enter a valid medicine, batch, future expiry, positive quantity and non-negative cost.');
}

try {
    $pdo->beginTransaction();
    $upsert = $pdo->prepare(
        'INSERT INTO batches (medicine_id, batch_no, expiry_date, quantity, cost_price)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
    );
    $upsert->execute([$medicineId, $batchNo, $expiryDate, $quantity, $costPrice]);

    $findBatch = $pdo->prepare(
        'SELECT batch_id FROM batches WHERE medicine_id = ? AND batch_no = ? FOR UPDATE'
    );
    $findBatch->execute([$medicineId, $batchNo]);
    $batchId = $findBatch->fetchColumn();
    if ($batchId === false) {
        throw new RuntimeException('Received batch was not found after save.');
    }

    $movement = $pdo->prepare(
        "INSERT INTO stock_movements (batch_id, medicine_id, move_type, qty, user_id)
         VALUES (?, ?, 'RECEIVE', ?, ?)"
    );
    $movement->execute([$batchId, $medicineId, $quantity, (int)$_SESSION['uid']]);
    $pdo->commit();

    api_send([
        'success' => true,
        'message' => 'Received ' . $quantity . ' units into batch ' . $batchNo . '.',
    ]);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PharmaGuard receive API: ' . $error->getMessage());
    api_fail(500, 'Could not save the batch. Check that the selected medicine exists.');
}
