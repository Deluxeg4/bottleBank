<?php
// delete.php
session_start();
header('Content-Type: application/json; charset=utf-8');

// ต้องเป็นแอดมินเท่านั้น
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// ต้องเป็น POST เท่านั้น
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'ใช้เมธอดไม่ถูกต้อง (ต้องเป็น POST)'
    ]);
    exit;
}

require_once __DIR__ . '/../database/database.php';

// ตรวจสอบมี id ไหม
if (!isset($_POST['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบ ID'
    ]);
    exit;
}

// บังคับให้เป็น int
$id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
if ($id === false || $id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'รูปแบบ ID ไม่ถูกต้อง'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM bottle_entries WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'ลบข้อมูลสำเร็จ'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบข้อมูลหรือลบไม่สำเร็จ'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database Error: ' . $e->getMessage()
    ]);
}
