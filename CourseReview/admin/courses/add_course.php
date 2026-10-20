<?php
session_start();
require_once '../../config/Database.php';

// ===== ตรวจสิทธิ์ผู้ดูแล =====
// ตรวจ session และ role ก่อนอนุญาตให้เพิ่มรายวิชา
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// ===== เตรียมคลาสและหมวดหมู่ =====
require_once '../../classes/Course.php';
$database = new Database();
$db = $database->getConnection();
$course = new Course($db);

// เรียก Course::getCategories() เพื่อเตรียมตัวเลือกหมวดหมู่สำหรับแบบฟอร์ม
$stmt_cats = $course->getCategories();
$categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

// ===== รับข้อมูลฟอร์มและเพิ่มรายวิชา =====
// เมื่อได้รับคำขอ POST ให้ Course::create() บันทึกข้อมูลที่ส่งมาจากฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_course'])) {
    if ($course->create($_POST['course_id'], $_POST['course_name'], $_POST['teacher_name'], $_POST['credits'], $_POST['category_id'])) {
        // เมื่อเพิ่มสำเร็จ บันทึกประวัติด้วย Log::addLog() แล้วกลับไปหน้ารวมรายวิชา
        require_once '../../classes/Log.php';
        $log = new Log($db);
        $log->addLog($_SESSION['user_id'], 'add_course', 'เพิ่มวิชาใหม่: ' . $_POST['course_id'] . ' (' . $_POST['course_name'] . ')', 'course_detail.php?id=' . urlencode($_POST['course_id']));
        header("Location: manage_courses.php"); exit();
    }
}

// ===== เตรียมส่วนหัวและแสดงแบบฟอร์ม =====
$page_title = "เพิ่มวิชาใหม่ - Course Review";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

?>
<!-- แบบฟอร์มรับข้อมูลรายวิชาและส่งด้วยคำขอ POST -->
<div class="container max-w-800 py-5">
    <div class="mb-3">
        <a href="manage_courses.php" class="text-muted no-underline">⬅️ กลับหน้ารายวิชา</a>
    </div>
    <div class="card">
        <h3 class="mb-4 text-dark">เพิ่มรายวิชาใหม่</h3>
        <form action="add_course.php" method="POST" class="form-grid-2">
            <div class="input-group"><label>รหัสวิชา</label><input type="text" name="course_id" required></div>
            <div class="input-group"><label>ชื่อวิชา</label><input type="text" name="course_name" required></div>
            <div class="input-group"><label>ชื่ออาจารย์ผู้สอน</label><input type="text" name="teacher_name" required></div>
            <div class="input-group"><label>หน่วยกิต</label><input type="number" name="credits" min="1" max="6" value="3" required></div>
            <div class="input-group" style="grid-column: 1 / -1;">
                <div class="input-group">
                <label>หมวดหมู่วิชา</label>
                <select name="category_id" required>
                    <option value="" disabled selected>-- เลือกหมวดหมู่วิชา --</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>">
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            </div>
            <div class="text-right mt-2" style="grid-column: 1 / -1;">
                <button type="submit" name="add_course" class="btn w-auto px-4">บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>