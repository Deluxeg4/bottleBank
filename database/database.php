<?php
$host = 'localhost';
$db   = 'bottlebank';  // ให้ตรงกับที่สร้างใน SQL
$user = 'root';        // ตามที่ตั้งใน XAMPP / MySQL
$pass = '';            // ถ้ามีรหัสผ่านให้ใส่

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $e->getMessage());
}
