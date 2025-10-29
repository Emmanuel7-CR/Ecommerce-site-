<?php
require_once __DIR__.'/header.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="orders.csv"');

$out = fopen('php://output','w');
fputcsv($out, ['id','reference','user_id','total_amount','status','created_at']);

$stmt = $conn->prepare("SELECT id, reference, user_id, total_amount, status, created_at FROM orders ORDER BY id");
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    fputcsv($out, [$r['id'],$r['reference'],$r['user_id'],$r['total_amount'],$r['status'],$r['created_at']]);
}
$stmt->close();
fclose($out);
exit;
