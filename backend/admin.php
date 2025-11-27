<?php
session_start();
require_once __DIR__ . '/../database/database.php';

// ถ้ายังไม่ login ให้เด้งไปหน้า login
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// ดึงข้อมูลแอดมิน
$username = $_SESSION['admin'];
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

// -----------------------------
// 1) รับและบันทึกข้อมูลจากฟอร์ม
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['firstName'])) {
    $firstName   = trim($_POST['firstName']);
    $lastName    = trim($_POST['lastName']);
    $studentId   = trim($_POST['studentId']);
    $score       = (int)($_POST['score'] ?? 0);
    $bottleCount = (int)($_POST['bottleCount'] ?? 0);
    $bottleType  = trim($_POST['bottleType'] ?? 'พลาสติก');
    $bottleSize  = trim($_POST['bottleSize'] ?? 'เล็ก');

    if ($firstName !== '' && $lastName !== '' && $studentId !== '') {
        $stmt = $pdo->prepare("
            INSERT INTO bottle_entries 
            (student_id, first_name, last_name, score, bottle_count, bottle_type, bottle_size)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $studentId,
            $firstName,
            $lastName,
            $score,
            $bottleCount,
            $bottleType,
            $bottleSize
        ]);

        // ป้องกันการกด F5 แล้วข้อมูลซ้ำ (Post/Redirect/Get)
        header("Location: admin.php?success=1");
        exit;
    }
}

// -----------------------------
// 2) ดึงข้อมูลสถิติจากฐานข้อมูล
// -----------------------------

// 2.1 คะแนนรวม, จำนวนรายการ, จำนวนขวดรวม
$stmt = $pdo->query("
    SELECT 
        COALESCE(SUM(score), 0) AS totalScore,
        COUNT(*) AS totalEntries,
        COALESCE(SUM(bottle_count), 0) AS totalBottles
    FROM bottle_entries
");
$stats = $stmt->fetch();

$totalScore   = (int)($stats['totalScore'] ?? 0);
$totalEntries = (int)($stats['totalEntries'] ?? 0);
$totalBottles = (int)($stats['totalBottles'] ?? 0);

// 2.2 จำนวนประเภทขวด
$stmt = $pdo->query("SELECT COUNT(DISTINCT bottle_type) AS bottleTypesCount FROM bottle_entries");
$bottleTypesCount = (int)($stmt->fetchColumn() ?: 0);

// 2.3 รายละเอียดจำนวนขวดแต่ละประเภท
$stmt = $pdo->query("
    SELECT bottle_type, SUM(bottle_count) AS total_bottles
    FROM bottle_entries
    GROUP BY bottle_type
    ORDER BY total_bottles DESC
");
$typeRows = $stmt->fetchAll();

if ($typeRows) {
    $parts = [];
    foreach ($typeRows as $row) {
        $parts[] = $row['bottle_type'] . ': ' . (int)$row['total_bottles'] . ' ขวด';
    }
    $bottleTypesDetailText = implode(', ', $parts);
} else {
    $bottleTypesDetailText = 'ยังไม่มีข้อมูล';
}

// 2.4 ดึงข้อมูลทั้งหมดสำหรับตารางรายการ
$stmt = $pdo->query("
    SELECT *
    FROM bottle_entries
    ORDER BY created_at DESC
");
$entries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดแอดมิน - ระบบจัดการข้อมูลขวด</title>
    <link rel="stylesheet" href="style.css">
    <!-- สำหรับ chart (ถ้าอยากใช้ Chart.js) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <div style="text-align: right; margin: 10px;">
            <a href="logout.php" class="btn-logout">ออกจากระบบ</a>
        </div>

        <div class="glass-card header-card">
            <div class="header-content">
                <div class="icon-box">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                </div>
                <div>
                    <h1>แดชบอร์ดแอดมิน</h1>
                    <p>ระบบจัดการข้อมูลขวดและคะแนน</p>
                    <p style="font-size: 0.9rem; opacity: 0.8;">
                        เข้าสู่ระบบโดย: <strong><?php echo htmlspecialchars($user['username'] ?? ''); ?></strong>
                    </p>
                </div>
            </div>
        </div>

        <!-- Form Card -->
        <div class="glass-card form-card">
            <div class="card-header">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <h2>เพิ่มข้อมูลนักเรียน</h2>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="glass-card" style="background: rgba(34, 197, 94, 0.3); margin-bottom: 1rem; padding: 0.75rem;">
                    <p style="color: #ecfdf5; font-size: 0.9rem;">บันทึกข้อมูลเรียบร้อยแล้ว</p>
                </div>
            <?php endif; ?>

            <form id="entryForm" method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>ชื่อนักเรียน</label>
                        <input type="text" id="firstName" name="firstName" placeholder="ระบุชื่อ" required>
                    </div>
                    <div class="form-group">
                        <label>นามสกุล</label>
                        <input type="text" id="lastName" name="lastName" placeholder="ระบุนามสกุล" required>
                    </div>
                    <div class="form-group">
                        <label>รหัสประจำตัวนักเรียน</label>
                        <input type="text" id="studentId" name="studentId" placeholder="เช่น 12345" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>คะแนน</label>
                        <input type="number" id="score" name="score" placeholder="0" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>จำนวนขวด</label>
                        <input type="number" id="bottleCount" name="bottleCount" placeholder="0" required>
                    </div>
                    <div class="form-group">
                        <label>ประเภทของขวด</label>
                        <select id="bottleType" name="bottleType">
                            <option value="พลาสติก">พลาสติก</option>
                            <option value="แก้ว">แก้ว</option>
                            <option value="อลูมิเนียม">อลูมิเนียม</option>
                            <option value="กระดาษ">กระดาษ</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ขนาดของขวด</label>
                        <select id="bottleSize" name="bottleSize">
                            <option value="เล็ก">เล็ก (น้อยกว่า 500ml)</option>
                            <option value="กลาง">กลาง (500ml - 1L)</option>
                            <option value="ใหญ่">ใหญ่ (มากกว่า 1L)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-submit">
                            <svg class="icon-small" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            เพิ่มข้อมูล
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Stats Card -->
        <div class="glass-card stats-card">
            <div class="stats-header">
                <div class="card-header">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <h2>สถิติ</h2>
                </div>

                <div class="stats-controls">
                    <div class="tabs">
                        <button class="tab-btn active" data-period="day">รายวัน</button>
                        <button class="tab-btn" data-period="month">รายเดือน</button>
                        <button class="tab-btn" data-period="year">รายปี</button>
                    </div>
                    <input type="date" id="selectedDate" class="date-input">
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                            <polyline points="17 6 23 6 23 12"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">คะแนนรวม</div>
                        <div class="stat-value" id="totalScore"><?php echo (int)$totalScore; ?></div>
                        <div class="stat-subtitle" id="scoreSubtitle">
                            <?php echo (int)$totalEntries; ?> รายการ
                        </div>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">ขวดรวม</div>
                        <div class="stat-value" id="totalBottles"><?php echo (int)$totalBottles; ?></div>
                        <div class="stat-subtitle">ทุกประเภท</div>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                            <path d="M11 8a3 3 0 0 1 3 3"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">ประเภทขวด</div>
                        <div class="stat-value" id="bottleTypes"><?php echo (int)$bottleTypesCount; ?></div>
                        <div class="stat-subtitle" id="bottleTypesDetail">
                            <?php echo htmlspecialchars($bottleTypesDetailText); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="chart-container">
                <canvas id="statsChart"></canvas>
            </div>
        </div>

        <!-- Data Table -->
        <div class="glass-card table-card">
            <div class="card-header">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 3h18v18H3z"></path>
                    <path d="M3 9h18"></path>
                    <path d="M3 15h18"></path>
                    <path d="M9 3v18"></path>
                </svg>
                <h2>ข้อมูลทั้งหมด</h2>
            </div>

            <div id="dataTable" class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>วันที่</th>
                            <th>รหัสนักเรียน</th>
                            <th>ชื่อ - นามสกุล</th>
                            <th>คะแนน</th>
                            <th>จำนวนขวด</th>
                            <th>ประเภทขวด</th>
                            <th>ขนาดขวด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($entries)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center; opacity:0.7;">
                                    ยังไม่มีข้อมูล
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($entries as $row): ?>
                                <tr>
                                    <td>
                                        <?php 
                                            echo htmlspecialchars(
                                                date('d/m/Y H:i', strtotime($row['created_at']))
                                            ); 
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td><?php echo (int)$row['score']; ?></td>
                                    <td><?php echo (int)$row['bottle_count']; ?></td>
                                    <td><?php echo htmlspecialchars($row['bottle_type']); ?></td>
                                    <td><?php echo htmlspecialchars($row['bottle_size']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
