<?php
require_once __DIR__ . '/../database/database.php';

// รายชื่อสุ่ม
$firstNames = ["กิตติ", "ธานี", "ปิยดา", "พิมพ์ชนก", "ธนพล", "วรัญญา", "กรกนก", "นที", "พีรพัฒน์", "สุรเดช", "เกวลิน", "ปรางค์ทิพย์", "ลลิตา", "มนัสวี", "พชร", "เจษฎา"];
$lastNames  = ["ชัยวัฒน์", "ทองดี", "บุญมา", "อินทร์แก้ว", "คำสิงห์", "นาคำ", "ไชยยศ", "แสงทอง", "คำภา", "เพ็งดี", "สมบูรณ์", "ภูมิจิต", "นิลเพชร", "ศรีสวัสดิ์"];

$bottleTypes = ["พลาสติก", "แก้ว", "อลูมิเนียม", "กระดาษ"];
$bottleSizes = ["เล็ก", "กลาง", "ใหญ่"];

$insertCount = 1000;

$stmt = $pdo->prepare("
    INSERT INTO bottle_entries
    (student_id, first_name, last_name, score, bottle_count, bottle_type, bottle_size, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

for ($i = 0; $i < $insertCount; $i++) {

    $studentId = str_pad(rand(10000, 99999), 5, "0", STR_PAD_LEFT);

    $firstName = $firstNames[array_rand($firstNames)];
    $lastName  = $lastNames[array_rand($lastNames)];

    $score       = rand(1, 30);
    $bottleCount = rand(1, 20);

    $type = $bottleTypes[array_rand($bottleTypes)];
    $size = $bottleSizes[array_rand($bottleSizes)];

    // วันที่ย้อนหลัง 60 วัน
    $daysAgo = rand(0, 60);
    $time = strtotime("-$daysAgo days") + rand(0, 86400);
    $createdAt = date("Y-m-d H:i:s", $time);

    $stmt->execute([
        $studentId,
        $firstName,
        $lastName,
        $score,
        $bottleCount,
        $type,
        $size,
        $createdAt
    ]);
}

echo "<h1>เพิ่มข้อมูล 500 แถวเรียบร้อยแล้ว!</h1>";
?>
