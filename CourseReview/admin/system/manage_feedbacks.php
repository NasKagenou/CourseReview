<?php
session_start();
require_once '../../config/Database.php';

// ===== ตรวจสิทธิ์ผู้ดูแล =====
// ตรวจ session และ role ก่อนเปิดหน้าจัดการ Feedback
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../../auth/login.php"); 
    exit(); 
}

// ===== เตรียมคลาสสำหรับจัดการ Feedback และบันทึกประวัติ =====
require_once '../../classes/Feedback.php';
require_once '../../classes/Log.php';

$database = new Database(); 
$db = $database->getConnection();
$feedbackObj = new Feedback($db);
$log = new Log($db);

// ===== รับคำขอเปลี่ยนสถานะ =====
// หาก URL มี resolve_id ให้ใช้ Feedback::updateStatus() ทำเครื่องหมายว่าเรื่องแก้ไขแล้ว
if (isset($_GET['resolve_id'])) {
    $feedbackObj->updateStatus($_GET['resolve_id'], 'resolved');
    
    // บันทึกผลด้วย Log::addLog() แล้วเปลี่ยนเส้นทางกลับหน้ารายการ
    $log->addLog($_SESSION['user_id'], 'resolve_feedback', 'รับทราบและแก้ไข Feedback รหัส: ' . $_GET['resolve_id'], 'admin/system/manage_feedbacks.php');
    
    header("Location: manage_feedbacks.php"); 
    exit();
}

// ===== อ่านรายการและเตรียมส่วนหัว =====
$stmt_feedbacks = $feedbackObj->readAll();

$page_title = "จัดการ Feedback - Course Review";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>
<!-- ===== แสดงรายการ Feedback ===== -->
<div class="container py-5">
    <div class="mb-3">
        <a href="../index.php" class="text-muted no-underline">⬅️️ กลับไปหน้าแดชบอร์ด</a>
    </div>
    <h2 class="text-dark mb-4 m-0">กล่องข้อความจากผู้ใช้งาน (Feedback)</h2>

    <div class="card p-0">
        <div style="overflow-x: auto;">
            <table class="table mb-0 w-full">
                <thead class="bg-gray-light">
                    <tr>
                        <th style="width: 15%;">วัน-เวลา</th>
                        <th style="width: 15%;">ผู้ใช้งาน</th>
                        <th style="width: 20%;">หัวข้อ</th>
                        <th style="width: 35%;">รายละเอียด</th>
                        <th style="width: 15%; text-align: center;">สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- วนแสดง Feedback ทีละรายการ พร้อมสถานะและลิงก์สำหรับจัดการ -->
                    <?php if($stmt_feedbacks->rowCount() > 0): while ($row = $stmt_feedbacks->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td class="text-xs text-muted"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                        <td><strong class="text-dark"><?php echo htmlspecialchars($row['username']); ?></strong></td>
                        <td><span class="font-medium text-primary"><?php echo htmlspecialchars($row['subject']); ?></span></td>
                        <td class="text-sm text-muted leading-relaxed"><?php echo nl2br(htmlspecialchars($row['message'])); ?></td>
                        <td class="text-center">
                            <?php if($row['status'] == 'pending'): ?>
                                <a href="manage_feedbacks.php?resolve_id=<?php echo $row['feedback_id']; ?>" class="btn-outline px-3 py-1 text-xs no-underline" onclick="return confirm('ทำเครื่องหมายว่ารับทราบ/แก้ไขแล้ว?');">รอดำเนินการ</a>
                            <?php else: ?>
                                <span class="badge badge-success m-0">✅ แก้ไขแล้ว</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">ยังไม่มีข้อความส่งเข้ามา</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>