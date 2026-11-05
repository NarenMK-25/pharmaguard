<?php
require __DIR__ . '/_bootstrap.php';
api_require_method('POST');
api_require_user();
api_check_csrf();

$medicineId = filter_var($_POST['medicine_id'] ?? null, FILTER_VALIDATE_INT);
$quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
if (!$medicineId || !$quantity || $quantity < 1) {
    api_fail(400, 'Choose a medicine and enter a quantity greater than 0.');
}

try {
    $pdo->beginTransaction();
    $pick = $pdo->prepare(
        'SELECT batch_id, quantity FROM batches
         WHERE medicine_id = ? AND quantity > 0 AND expiry_date >= CURDATE()
         ORDER BY expiry_date, batch_id FOR UPDATE'
    );
    $pick->execute([$medicineId]);

    $plan = [];
    $need = $quantity;
    $available = 0;
    foreach ($pick->fetchAll() as $batch) {
        $have = (int)$batch['quantity'];
        $available += $have;
        if ($need > 0) {
            $take = min($have, $need);
            $plan[] = [(int)$batch['batch_id'], $take];
            $need -= $take;
        }
    }

    if ($need > 0) {
        $pdo->rollBack();
        api_fail(409, 'Not enough unexpired stock. Available: ' . $available . '.');
    }

    $update = $pdo->prepare('UPDATE batches SET quantity = quantity - ? WHERE batch_id = ?');
    $movement = $pdo->prepare(
        "INSERT INTO stock_movements (batch_id, medicine_id, move_type, qty, user_id)
         VALUES (?, ?, 'DISPENSE', ?, ?)"
    );
    foreach ($plan as [$batchId, $take]) {
        $update->execute([$take, $batchId]);
        $movement->execute([$batchId, $medicineId, $take, (int)$_SESSION['uid']]);
    }

    $pdo->commit();
    api_send([
        'success' => true,
        'message' => 'Dispensed ' . $quantity . ' units from ' . count($plan)
            . ' batch(es), earliest expiry first.',
    ]);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PharmaGuard dispense API: ' . $error->getMessage());
    api_fail(500, 'Could not complete the stock transaction.');
}
