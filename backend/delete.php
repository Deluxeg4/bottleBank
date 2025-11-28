<?php
// delete.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../database/database.php';

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบ ID']);
    exit;
}

$id = $_POST['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM bottle_entries WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'ลบข้อมูลสำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลหรือลบไม่สำเร็จ']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>