<?php
session_start();
require_once '../../config/Database.php';

// ===== ตรวจสิทธิ์ผู้ดูแล =====
// ตรวจ session และ role ก่อนอนุญาตให้เปิดหน้าประวัติกิจกรรม
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// ===== อ่านประวัติกิจกรรม =====
// สร้างการเชื่อมต่อและให้คลาส Log อ่านรายการทั้งหมดสำหรับตาราง
require_once '../../classes/Log.php';
$database = new Database();
$db = $database->getConnection();
$log = new Log($db);

$stmt_logs = $log->getAllLogs();

// ===== เตรียมส่วนหัวของหน้า =====
$page_title = "ประวัติการใช้งาน (Activity Logs)";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// ===== เตรียมป้ายประเภทกิจกรรม =====
// แปลงค่า action เป็นป้ายข้อความและสี เพื่อให้แยกประเภทกิจกรรมในตารางได้ง่าย
function getBadge($action) {
    // แสดงป้ายเฉพาะตามประเภทกิจกรรมที่ระบบรู้จัก
    if ($action == 'add_course') return '<span class="badge badge-success">✅ เพิ่มวิชา</span>';
    if ($action == 'edit_course') return '<span class="badge bg-warning-light text-warning">✏️ แก้ไขวิชา</span>';
    if ($action == 'add_free_course') return '<span class="badge badge-success">🎁 เพิ่มคอร์สฟรี</span>';
    if ($action == 'add_review') return '<span class="badge bg-primary-light text-primary">⭐ รีวิววิชา</span>';
    if ($action == 'add_reply') return '<span class="badge bg-gray-light text-muted">💬 ตอบกลับ</span>';
    if ($action == 'delete_review') return '<span class="badge bg-danger-light text-danger">🗑️ ลบรีวิว</span>';
    
    // ตรวจประเภทกิจกรรมอื่น ๆ และคืนป้ายที่ตรงกับงานนั้น
    if ($action == 'suggest_course') return '<span class="badge bg-primary-light text-primary">💡 เสนอวิชา</span>';
    if ($action == 'reject_suggestion') return '<span class="badge bg-danger-light text-danger">❌ ปฏิเสธวิชา</span>';
    if ($action == 'add_feedback') return '<span class="badge bg-warning-light text-warning">📣 ส่ง Feedback</span>';
    if ($action == 'resolve_feedback') return '<span class="badge badge-success">✅ แก้ปัญหา</span>';
    
    return '<span class="badge">บันทึก</span>';
}
?>
<!-- ===== แสดงรายการประวัติกิจกรรม ===== -->
<div class="container py-5">
    <div class="mb-3 flex justify-between items-center">
        <div>
            <a href="../index.php" class="text-muted no-underline">⬅️ กลับไปหน้าแดชบอร์ด</a>
            <h2 class="text-dark mt-2 mb-0">ประวัติกิจกรรมในระบบ (Logs)</h2>
        </div>
    </div>

    <div class="card p-0">
        <div>
            <table class="table mb-0 w-full">
                <thead class="bg-gray-light">
                    <tr>
                        <th class="text-left">วัน-เวลา</th>
                        <th class="text-left">ผู้ทำรายการ</th>
                        <th class="text-left">ประเภท</th>
                        <th class="text-left">รายละเอียด</th>
                        <th class="text-center">ดูต้นทาง</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- อ่านผลลัพธ์ทีละรายการ แล้วแสดงวันเวลา ผู้ทำรายการ ประเภท และรายละเอียด -->
                    <?php if($stmt_logs->rowCount() > 0): while ($row = $stmt_logs->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td class="text-xs text-muted"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                        <td>
                            <strong class="text-dark"><?php echo htmlspecialchars($row['username']); ?></strong>
                            <?php if($row['role'] == 'admin'): ?>
                                <span class="text-danger text-xs">(แอดมิน)</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo getBadge($row['action']); ?></td>
                        <td class="text-sm text-muted"><?php echo htmlspecialchars($row['details']); ?></td>
                        <td class="text-center">
                            <!-- สร้างลิงก์ไปยังต้นทาง; กิจกรรมรีวิวจะชี้ตรงการ์ดรีวิวและเปิดในแท็บใหม่ -->
                            <a href="<?php echo htmlspecialchars($log->getTargetLink($row['action'], $row['target_url'], $row['review_id'])); ?>" target="_blank" rel="noopener noreferrer" class="btn-outline px-3 py-1 text-xs no-underline">
                                ไปที่ต้นทาง
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">ยังไม่มีประวัติการทำรายการในระบบ</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>