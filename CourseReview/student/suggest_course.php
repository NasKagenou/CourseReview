<?php
// ===== เริ่มต้น session และตรวจสิทธิ์ =====
session_start();
require_once '../config/Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// ===== เตรียมคลาสและหมวดหมู่ที่ใช้ในแบบฟอร์ม =====
require_once '../classes/Suggestion.php';
require_once '../classes/Course.php'; 
require_once '../classes/Log.php';

$database = new Database();
$db = $database->getConnection();
$suggestionObj = new Suggestion($db);
$courseObj = new Course($db);
$log = new Log($db);

$stmt_cats = $courseObj->getCategories();
$categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

$msg = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_suggestion'])) {
    // ตรวจรหัสวิชาก่อนสร้างข้อเสนอ เพื่อแจ้งผู้ใช้หากมีวิชานี้อยู่แล้ว
    $check_exist = $courseObj->readOne($_POST['course_id']);
    if ($check_exist) {
        $msg = "วิชานี้มีในระบบอยู่แล้วครับ สามารถไปรีวิวได้เลย";
        $msg_type = "danger";
    } else {
        if ($suggestionObj->create($_SESSION['user_id'], strtoupper(trim($_POST['course_id'])), trim($_POST['course_name']), trim($_POST['teacher_name']), $_POST['credits'], $_POST['category_id'])) {
            $msg = "ส่งข้อเสนอสำเร็จ! กรุณารอผู้ดูแลระบบตรวจสอบและอนุมัติ";
            $msg_type = "success";
            
            // เมื่อ Suggestion::create() สำเร็จ ให้ Log::addLog() บันทึกกิจกรรมการเสนอวิชา
            $log->addLog($_SESSION['user_id'], 'suggest_course', 'เสนอรายวิชาใหม่: ' . strtoupper(trim($_POST['course_id'])), 'student/suggest_course.php');
        }
    }
}

// ===== แสดงข้อความและแบบฟอร์มเสนอรายวิชา =====
$page_title = "เสนอรายวิชาใหม่ - Course Review";
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<div class="container max-w-800 py-5">
    <?php if (!empty($msg)): ?>
        <div class="bg-<?php echo $msg_type; ?>-light p-3 rounded-md mb-4 text-center text-sm font-medium">
            <?php echo $msg_type == 'success' ? '✅' : '❌'; ?> <?php echo $msg; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h3 class="mb-4 text-dark">💡 เสนอรายวิชาใหม่</h3>
        <p class="text-muted mb-4">หาวิชาที่อยากรีวิวไม่เจอใช่ไหม? กรอกข้อมูลด้านล่างเพื่อให้แอดมินเพิ่มวิชาเข้าระบบได้เลย!</p>
        
        <form action="suggest_course.php" method="POST" class="form-grid-2">
            <div class="input-group"><label>รหัสวิชา</label><input type="text" name="course_id" placeholder="เช่น GEN-101" required></div>
            <div class="input-group"><label>ชื่อวิชา</label><input type="text" name="course_name" required></div>
            <div class="input-group"><label>ชื่ออาจารย์ผู้สอน</label><input type="text" name="teacher_name" required></div>
            <div class="input-group"><label>หน่วยกิต</label><input type="number" name="credits" min="1" max="6" value="3" required></div>
            <div class="input-group" style="grid-column: 1 / -1;">
                <label>หมวดหมู่วิชา</label>
                <select name="category_id" required>
                    <option value="" disabled selected>- เลือกหมวดหมู่วิชา -</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="text-right mt-2" style="grid-column: 1 / -1;">
                <button type="submit" name="submit_suggestion" class="btn w-auto px-5">เสนอรายวิชา</button>
            </div>
        </form>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>