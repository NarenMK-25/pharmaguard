<?php
require __DIR__ . '/_bootstrap.php';
api_require_method('GET');
if (empty($_SESSION['uid']) && !public_demo_dashboard_enabled()) {
    api_fail(401, 'Not signed in, or session expired.');
}

try {
    $expiring = $pdo->query(
        "SELECT m.name AS medicine, b.batch_no, b.expiry_date, b.quantity,
                DATEDIFF(b.expiry_date, CURDATE()) AS days_left
         FROM batches b JOIN medicines m ON m.medicine_id = b.medicine_id
         WHERE b.quantity > 0
           AND b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
         ORDER BY b.expiry_date"
    )->fetchAll();

    $expired = $pdo->query(
        "SELECT m.name AS medicine, b.batch_no, b.expiry_date, b.quantity,
                DATEDIFF(CURDATE(), b.expiry_date) AS days_ago
         FROM batches b JOIN medicines m ON m.medicine_id = b.medicine_id
         WHERE b.quantity > 0 AND b.expiry_date < CURDATE()
         ORDER BY b.expiry_date"
    )->fetchAll();

    $lowStock = $pdo->query(
        "SELECT t.*,
                GREATEST(t.reorder_level * 2, t.used30) - t.stock AS suggested
         FROM (
             SELECT m.medicine_id, m.name AS medicine, m.reorder_level, s.name AS supplier,
                    CAST(COALESCE((
                        SELECT SUM(b.quantity) FROM batches b
                        WHERE b.medicine_id = m.medicine_id AND b.expiry_date >= CURDATE()
                    ), 0) AS SIGNED) AS stock,
                    CAST(COALESCE((
                        SELECT SUM(sm.qty) FROM stock_movements sm
                        WHERE sm.medicine_id = m.medicine_id
                          AND sm.move_type = 'DISPENSE'
                          AND sm.created_at >= NOW() - INTERVAL 30 DAY
                    ), 0) AS SIGNED) AS used30
             FROM medicines m
             LEFT JOIN suppliers s ON s.supplier_id = m.supplier_id
         ) t
         WHERE t.stock <= t.reorder_level
         ORDER BY t.stock / NULLIF(t.reorder_level, 0)"
    )->fetchAll();

    api_send([
        'expiring' => $expiring,
        'expired' => $expired,
        'lowStock' => $lowStock,
    ]);
} catch (PDOException $error) {
    error_log('PharmaGuard alerts API: ' . $error->getMessage());
    api_fail(500, 'Could not load alerts.');
}
