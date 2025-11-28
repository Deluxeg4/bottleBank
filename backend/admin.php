<?php
    session_start();
    // ตรวจสอบเส้นทางไฟล์ database.php ให้ถูกต้อง
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
            $stmt->execute([$studentId, $firstName, $lastName, $score, $bottleCount, $bottleType, $bottleSize]);
            // ป้องกันการกด F5 แล้วข้อมูลซ้ำ (Post/Redirect/Get)
            header("Location: admin.php?success=1");
            exit;
        }
    }

    // -----------------------------
    // 2) ดึงข้อมูลสถิติจากฐานข้อมูล (ส่วนหัว)
    // -----------------------------
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

    // ดึงประเภทขวด
    $stmt = $pdo->query("
        SELECT bottle_type, SUM(bottle_count) AS total_bottles
        FROM bottle_entries
        GROUP BY bottle_type
        ORDER BY total_bottles DESC
    ");
    $typeRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $bottleTypesCount = count($typeRows);
    $bottleTypesDetailText = 'ยังไม่มีข้อมูล';
    if ($typeRows) {
        $parts = [];
        foreach ($typeRows as $row) {
            $parts[] = $row['bottle_type'] . ': ' . (int)$row['total_bottles'] . ' ขวด';
        }
        $bottleTypesDetailText = implode(', ', $parts);
    }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดแอดมิน - ระบบจัดการข้อมูลขวด</title>
    <link rel="stylesheet" href="style.css"> 
    </head>
<body>
    <div class="app-container">
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

        <div class="glass-card stats-card">
             <div class="card-header">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                <h2>สถิติโดยรวม</h2>
            </div>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">คะแนนรวม</div>
                        <div class="stat-value" id="totalScore"><?php echo number_format($totalScore); ?></div>
                        <div class="stat-subtitle" id="scoreSubtitle"><?php echo number_format($totalEntries); ?> รายการ</div>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">
                         <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">ขวดรวม</div>
                        <div class="stat-value" id="totalBottles"><?php echo number_format($totalBottles); ?></div>
                        <div class="stat-subtitle">ทุกประเภท</div>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">
                         <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path><path d="M11 8a3 3 0 0 1 3 3"></path></svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">ประเภทขวด (<?php echo (int)$bottleTypesCount; ?>)</div>
                        <div class="stat-value" style="font-size: 1.5rem;"><?php echo htmlspecialchars($bottleTypesDetailText); ?></div>
                        <div class="stat-subtitle" id="bottleTypesDetail"></div>
                    </div>
                </div>
            </div>
        </div>

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
            
            <form onsubmit="return false;" style="margin-bottom: 2rem; padding: 0 2rem;">
                <div class="form-row">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="search_id">ค้นหาด้วยเลขประจำตัวนักเรียน/ชื่อ</label>
                        <input type="text" id="search_id" name="search_id" placeholder="ป้อนเลขประจำตัว ชื่อ หรือนามสกุล">
                    </div>
                </div>
            </form>

            <div id="dataTable" class="data-table">
                <div class="empty-state">กำลังโหลดข้อมูล...</div>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById("search_id");
        const tableDiv = document.getElementById("dataTable");

        // 1. ฟังก์ชันโหลดข้อมูล (Live Search)
        async function loadData(query = "") {
            tableDiv.innerHTML = '<div class="empty-state">กำลังโหลดข้อมูล...</div>';

            try {
                // เรียกข้อมูลจาก search.php (ต้องมี id คอลัมน์กลับมา)
                const res = await fetch("search.php?search=" + encodeURIComponent(query));
                if (!res.ok) {
                    tableDiv.innerHTML = '<div class="empty-state">โหลดข้อมูลไม่สำเร็จ</div>';
                    return;
                }
                const items = await res.json();

                if (!Array.isArray(items) || items.length === 0) {
                    tableDiv.innerHTML = '<div class="empty-state">ไม่พบข้อมูล</div>';
                    return;
                }

                // จัดกลุ่มตาม student_id
                const groups = {};
                items.forEach(row => {
                    const sid = row.student_id;
                    if (!groups[sid]) {
                        groups[sid] = {
                            full_name: (row.first_name || "") + " " + (row.last_name || ""),
                            total_score: 0,
                            total_bottles: 0,
                            rows: []
                        };
                    }
                    groups[sid].total_score += parseInt(row.score || 0);
                    groups[sid].total_bottles += parseInt(row.bottle_count || 0);
                    groups[sid].rows.push(row);
                });

                // สร้าง HTML
                let html = "";
                for (const sid in groups) {
                    const g = groups[sid];
                    html += `
                        <div class="student-group">
                            <div class="student-header">
                                ${escapeHtml(g.full_name)} (ID: ${escapeHtml(sid)})
                                <span style="font-size: 0.9rem;">
                                    คะแนนรวม: ${g.total_score} |
                                    จำนวนขวดรวม: ${g.total_bottles}
                                </span>
                            </div>
                    `;
                    g.rows.forEach(r => {
                        const createdAt = new Date(r.created_at);
                        const createdText = isNaN(createdAt.getTime())
                            ? r.created_at
                            : createdAt.toLocaleString("th-TH", {
                                year: "numeric", month: "2-digit", day: "2-digit",
                                hour: "2-digit", minute: "2-digit", hour12: false
                            });

                        html += `
                            <div class="entry-item">
                                <div class="entry-details">
                                    <div class="entry-field"><div class="entry-label">วันที่/เวลา</div><div class="entry-value">${escapeHtml(createdText)}</div></div>
                                    <div class="entry-field"><div class="entry-label">รหัสนักเรียน</div><div class="entry-value">${escapeHtml(r.student_id)}</div></div>
                                    <div class="entry-field"><div class="entry-label">คะแนน</div><div class="entry-value">${parseInt(r.score || 0)}</div></div>
                                    <div class="entry-field"><div class="entry-label">จำนวนขวด</div><div class="entry-value">${parseInt(r.bottle_count || 0)}</div></div>
                                    <div class="entry-field"><div class="entry-label">ประเภท/ขนาด</div><div class="entry-value">${escapeHtml(r.bottle_type)} / ${escapeHtml(r.bottle_size)}</div></div>
                                </div>

                                <button class="btn-delete" data-id="${r.id}" title="ลบรายการนี้">
                                    <svg class="icon-small" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        `;
                    });
                    html += `</div>`;
                }

                tableDiv.innerHTML = html;

                // **สำคัญ:** ผูก Event Listener ให้ปุ่มลบทุกปุ่ม
                document.querySelectorAll('.btn-delete').forEach(button => {
                    button.addEventListener('click', handleDelete);
                });

            } catch (e) {
                console.error(e);
                tableDiv.innerHTML = '<div class="empty-state">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
            }
        }

        // 2. ฟังก์ชันลบข้อมูล (Delete ทันที)
        async function handleDelete(e) {
            const button = e.currentTarget;
            const id = button.getAttribute('data-id');

            // **ลบได้ทันที**

            const formData = new FormData();
            formData.append('id', id);

            try {
                // ส่งคำขอ POST ไปยัง delete.php
                const res = await fetch('delete.php', { method: 'POST', body: formData });
                const result = await res.json();
                
                if(result.success) {
                    // ไม่ต้อง alert ก็ได้ ถ้าอยากให้เร็ว
                    // alert('ลบข้อมูลเรียบร้อย');
                    
                    // โหลดตารางใหม่เพื่อให้ข้อมูลที่ลบหายไปจากหน้าจอ
                    loadData(searchInput.value.trim()); 
                } else {
                    alert('เกิดข้อผิดพลาดในการลบ: ' + result.message);
                }
            } catch(err) {
                console.error("Delete Error:", err);
                alert('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้เพื่อลบข้อมูล');
            }
        }

        // 3. ฟังก์ชันป้องกัน XSS
        function escapeHtml(text) {
            if (text === null || text === undefined) return "";
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // โหลดข้อมูลทั้งหมดตอนเปิดหน้า
        loadData("");

        // อัปเดตเมื่อพิมพ์ (Live search)
        searchInput.addEventListener("input", () => {
            const value = searchInput.value.trim();
            loadData(value);
        });
    </script>
</body>
</html>