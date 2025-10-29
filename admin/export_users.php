<?php
require_once __DIR__.'/header.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="users.csv"');

$out = fopen('php://output','w');
fputcsv($out, ['id','name','email','phone','role','created_at']);

$stmt = $conn->prepare("SELECT id, name, email, phone, role, created_at FROM users ORDER BY id");
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    fputcsv($out, [$r['id'],$r['name'],$r['email'],$r['phone'],$r['role'],$r['created_at']]);
}
$stmt->close();
fclose($out);
exit;
