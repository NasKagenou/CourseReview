<?php
session_start();
require_once '../../config/Database.php';

// ===== ตรวจสิทธิ์ผู้ดูแล =====
// ตรวจ session และ role ก่อนอนุญาตให้แก้ไขข้อมูลรายวิชา
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// ===== เตรียมคลาสและอ่านรหัสวิชาจากคำขอ =====
require_once '../../classes/Course.php';
$database = new Database(); $db = $database->getConnection(); $course = new Course($db);
$course_id = isset($_GET['id']) ? $_GET['id'] : die('ไม่พบรหัสวิชา');

// ===== รับข้อมูลฟอร์มและบันทึกการแก้ไข =====
// เมื่อส่งคำขอแบบ POST ให้ Course::update() บันทึกค่าที่ส่งมาจากฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_course'])) {
    if ($course->update($_POST['course_id'], $_POST['course_name'], $_POST['teacher_name'], $_POST['credits'], $_POST['category_id'])) {
        // เมื่อบันทึกสำเร็จ ใช้ Log::addLog() เก็บประวัติ แล้วกลับหน้ารวมรายวิชา
        require_once '../../classes/Log.php';
        $log = new Log($db);
        $log->addLog($_SESSION['user_id'], 'edit_course', 'แก้ไขข้อมูลวิชา: ' . $_POST['course_id'], 'course_detail.php?id=' . urlencode($_POST['course_id']));
        header("Location: manage_courses.php"); exit();
    }
}
// อ่านข้อมูลเดิมของวิชาและรายการหมวดหมู่ เพื่อนำไปเติมในช่องกรอก
$edit_data = $course->readOne($course_id);

$stmt_cats = $course->getCategories();
$categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

// ===== เตรียมส่วนหัวและแสดงแบบฟอร์ม =====
$page_title = "แก้ไขวิชา - Course Review";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>
<!-- แบบฟอร์มแสดงข้อมูลเดิม และส่งค่าที่แก้ไขด้วยคำขอ POST -->
<div class="container max-w-800 py-5">
    <div class="mb-3">
        <a href="manage_courses.php" class="text-muted no-underline">⬅️ กลับหน้ารายวิชา</a>
    </div>
    <div class="card">
        <h3 class="mb-4 text-dark">แก้ไขข้อมูลรายวิชา</h3>
        <form action="edit_course.php?id=<?php echo urlencode($course_id); ?>" method="POST" class="form-grid-2">
            <div class="input-group">
                <label>รหัสวิชา</label>
                <input type="text" name="course_id" value="<?php echo htmlspecialchars($edit_data['course_id']); ?>" readonly class="readonly-input">
            </div>
            <div class="input-group"><label>ชื่อวิชา</label><input type="text" name="course_name" value="<?php echo htmlspecialchars($edit_data['course_name']); ?>" required></div>
            <div class="input-group"><label>ชื่ออาจารย์ผู้สอน</label><input type="text" name="teacher_name" value="<?php echo htmlspecialchars($edit_data['teacher_name']); ?>" required></div>
            <div class="input-group"><label>หน่วยกิต</label><input type="number" name="credits" min="1" max="6" value="<?php echo htmlspecialchars($edit_data['credits']); ?>" required></div>
            <div class="input-group" style="grid-column: 1 / -1;">
                <div class="input-group">
                <label>หมวดหมู่วิชา</label>
                <select name="category_id" required>
                    <?php foreach($categories as $cat): ?>
                        <!-- เลือกหมวดที่ตรงกับข้อมูลเดิมของวิชา เพื่อแสดงค่าปัจจุบันในแบบฟอร์ม -->
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo ($edit_data['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            </div>
            <div class="text-right mt-2" style="grid-column: 1 / -1;">
                <button type="submit" name="update_course" class="btn w-auto px-4">อัปเดตข้อมูล</button>
            </div>
        </form>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>