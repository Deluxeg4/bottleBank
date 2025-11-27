<?php
require_once __DIR__ . '/../database/database.php';

header('Content-Type: application/json; charset=utf-8');

$search = trim($_GET['search'] ?? '');

// ใช้ LIKE ให้ค้นหาแบบบางส่วนของเลขประจำตัวได้
// ถ้าอยากให้ค้นหาแบบตรงเป๊ะ เปลี่ยนเป็น WHERE student_id = ? 
$sql = "
    SELECT student_id, first_name, last_name, score, bottle_count, 
           bottle_type, bottle_size, created_at
    FROM bottle_entries
";
$params = [];

if ($search !== '') {
    $sql .= " WHERE student_id LIKE ? ";
    $params[] = "%{$search}%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data, JSON_UNESCAPED_UNICODE);
