<?php
// search.php
session_start();
header('Content-Type: application/json; charset=utf-8');

// ถ้าจะล็อกเฉพาะแอดมิน ก็ใช้บรรทัดนี้ได้
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../database/database.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    if ($search === '') {
        // ไม่ได้กรอกอะไรเลย -> ดึงทั้งหมด (หรือจะ LIMIT ตามต้องการก็ได้)
        $stmt = $pdo->prepare("
            SELECT 
                id,
                student_id,
                first_name,
                last_name,
                score,
                bottle_count,
                bottle_type,
                bottle_size,
                created_at
            FROM bottle_entries
            ORDER BY created_at DESC
        ");
        $stmt->execute();
    } else {
        // ค้นหาจากเลขประจำตัว / ชื่อ / นามสกุล
        $like = '%' . $search . '%';
        $stmt = $pdo->prepare("
            SELECT 
                id,
                student_id,
                first_name,
                last_name,
                score,
                bottle_count,
                bottle_type,
                bottle_size,
                created_at
            FROM bottle_entries
            WHERE student_id LIKE ?
               OR first_name LIKE ?
               OR last_name LIKE ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$like, $like, $like]);
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
