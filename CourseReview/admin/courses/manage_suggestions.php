<?php
session_start();
require_once '../../config/Database.php';

// ===== ตรวจสิทธิ์ผู้ดูแล =====
// ตรวจ session และ role ก่อนเปิดหน้าจัดการข้อเสนอรายวิชา
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../../auth/login.php"); 
    exit(); 
}

// ===== เตรียมคลาสที่ใช้จัดการข้อเสนอ =====
require_once '../../classes/Suggestion.php';
require_once '../../classes/Course.php';
require_once '../../classes/Log.php';

$database = new Database(); 
$db = $database->getConnection();
$suggestionObj = new Suggestion($db);
$courseObj = new Course($db);
$log = new Log($db);

// ===== รับคำขออนุมัติหรือปฏิเสธ =====
// อ่าน action และ id จาก URL เพื่อระบุข้อเสนอและผลการจัดการที่ผู้ดูแลเลือก
if (isset($_GET['action']) && isset($_GET['id'])) {
    $sug_id = $_GET['id'];
    $action = $_GET['action'];
    $sug_data = $suggestionObj->getOne($sug_id);

    if ($sug_data && $sug_data['status'] == 'pending') {
        if ($action == 'approve') {
            // เมื่อ Course::create() เพิ่มวิชาได้ จึงปรับสถานะและบันทึกผลด้วย Log::addLog()
            if ($courseObj->create($sug_data['course_id'], $sug_data['course_name'], $sug_data['teacher_name'], $sug_data['credits'], $sug_data['category_id'])) {
                $suggestionObj->updateStatus($sug_id, 'approved');
                $log->addLog($_SESSION['user_id'], 'add_course', 'อนุมัติวิชาจากข้อเสนอ: ' . $sug_data['course_id'], 'course_detail.php?id=' . urlencode($sug_data['course_id']));
            }
        } elseif ($action == 'reject') {
            $suggestionObj->updateStatus($sug_id, 'rejected');
            // บันทึกผลปฏิเสธด้วย Log::addLog() เพื่อให้ตรวจสอบย้อนหลังได้
            $log->addLog($_SESSION['user_id'], 'reject_suggestion', 'ปฏิเสธข้อเสนอวิชา: ' . $sug_data['course_id'], 'admin/courses/manage_suggestions.php');
        }
    }
    // ไม่ว่าผลการจัดการจะเป็นอย่างไร ให้กลับไปยังหน้ารายการข้อเสนอ
    header("Location: manage_suggestions.php"); 
    exit();
}

// ===== อ่านข้อเสนอและเตรียมส่วนหัว =====
$stmt_suggestions = $suggestionObj->readAll();

$page_title = "จัดการข้อเสนอรายวิชา - Course Review";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>
<!-- ===== แสดงรายการข้อเสนอและผลการจัดการ ===== -->
<div class="container py-5">
    <div class="mb-3">
        <a href="../index.php" class="text-muted no-underline">⬅️ กลับไปหน้าแดชบอร์ด</a>
    </div>
    <h2 class="text-dark mb-4 m-0">คำขอเสนอรายวิชาใหม่</h2>

    <div class="card p-0">
        <div style="overflow-x: auto;">
            <table class="table mb-0 w-full">
                <thead class="bg-gray-light">
                    <tr>
                        <th style="width: 15%;">นศ.ที่เสนอ</th>
                        <th style="width: 35%;">ข้อมูลวิชา</th>
                        <th style="width: 20%;">หมวดหมู่</th>
                        <th style="width: 30%; text-align: center;">จัดการ / สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- แสดงข้อมูลผู้เสนอและรายละเอียด พร้อมปุ่มตามสถานะของข้อเสนอ -->
                    <?php if($stmt_suggestions->rowCount() > 0): while ($row = $stmt_suggestions->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><strong class="text-dark"><?php echo htmlspecialchars($row['username']); ?></strong></td>
                        <td>
                            <div class="font-medium text-primary"><?php echo htmlspecialchars($row['course_id']); ?></div>
                            <div class="text-sm text-dark"><?php echo htmlspecialchars($row['course_name']); ?></div>
                            <div class="text-xs text-muted mt-1">อ.<?php echo htmlspecialchars($row['teacher_name']); ?> | <?php echo $row['credits']; ?> หน่วยกิต</div>
                        </td>
                        <td><span class="badge m-0"><?php echo htmlspecialchars($row['category_name']); ?></span></td>
                        <td class="text-center">
                            <?php if($row['status'] == 'pending'): ?>
                                <div class="suggestion-actions">
                                    <a href="manage_suggestions.php?action=approve&id=<?php echo $row['suggestion_id']; ?>" class="btn-success suggestion-action text-xs" onclick="return confirm('อนุมัติและเพิ่มวิชานี้เข้าระบบ?');">✅ อนุมัติ</a>
                                    <a href="manage_suggestions.php?action=reject&id=<?php echo $row['suggestion_id']; ?>" class="btn-outline suggestion-action text-xs danger-hover" onclick="return confirm('ปฏิเสธคำขอนี้?');">❌ ปฏิเสธ</a>
                                </div>
                            <?php elseif($row['status'] == 'approved'): ?>
                                <span class="badge badge-success m-0">อนุมัติแล้ว</span>
                            <?php else: ?>
                                <span class="badge bg-danger-light text-danger m-0">ถูกปฏิเสธ</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="4" class="text-center py-4 text-muted">ยังไม่มีข้อเสนอรายวิชาใหม่</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>