<?php
session_start();
require_once '../../config/Database.php';

// ===== ตรวจสิทธิ์ผู้ดูแล =====
// ตรวจ session และ role ก่อนอนุญาตให้เพิ่มคอร์สฟรี
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// ===== เตรียมคลาสและสถานะข้อความ =====
require_once '../../classes/FreeCourse.php';
$database = new Database();
$db = $database->getConnection();
$courseObj = new FreeCourse($db);

$msg_error = "";

// ===== รับข้อมูลฟอร์มและเพิ่มคอร์ส =====
// ทำงานส่วนนี้เมื่อได้รับคำขอ POST ที่ส่งมาจากปุ่มบันทึก
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_free_course'])) {
    
    // เรียก FreeCourse::uploadImage() เพื่อจัดเก็บรูป และใช้รูปสำรองเมื่อไม่มีชื่อรูป
    $cover_image = $courseObj->uploadImage($_FILES['cover_image']);
if (!$cover_image) { $cover_image = 'no_image.png'; }

    // เรียก FreeCourse::create() เพื่อบันทึกข้อมูลและรับรหัสคอร์สที่สร้างขึ้น
    if (empty($msg_error)) {
        $new_course_id = $courseObj->create($_POST['title'], $_POST['provider'], $_POST['category'], $_POST['level'], $_POST['duration'], $_POST['language'], $_POST['has_cert'], $_POST['course_url'], $cover_image);
        
        if ($new_course_id) {
            // เมื่อเพิ่มสำเร็จ บันทึกประวัติด้วย Log::addLog() แล้วเปลี่ยนไปหน้ารายการ
            require_once '../../classes/Log.php';
            $log = new Log($db);
            $log->addLog($_SESSION['user_id'], 'add_free_course', 'เพิ่มคอร์สฟรี: ' . $_POST['title'], 'admin/free_courses/edit_free_course.php?id=' . $new_course_id);
            
            header("Location: manage_free_courses.php"); 
            exit();
        } else {
            $msg_error = "เกิดข้อผิดพลาดในการบันทึกข้อมูลลงฐานข้อมูล";
        }
    }
}

// ===== เตรียมส่วนหัวและแสดงแบบฟอร์ม =====
$page_title = "เพิ่มคอร์สฟรี - Course Review";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>
<!-- แบบฟอร์มรับรายละเอียดคอร์สและแสดงข้อความผิดพลาดเมื่อบันทึกไม่สำเร็จ -->
<div class="container max-w-850 py-5">
    <div class="mb-3">
        <a href="manage_free_courses.php" class="text-muted no-underline">⬅️ กลับหน้ารายการคอร์ส</a>
    </div>
    
    <?php if(!empty($msg_error)): ?>
        <div class="bg-danger-light p-3 rounded-md mb-4 text-center text-sm font-medium">❌ <?php echo $msg_error; ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 class="mb-4 text-dark">เพิ่มคอร์สเรียนฟรี (CourseFree)</h3>
        
        <form action="add_free_course.php" method="POST" enctype="multipart/form-data" class="form-grid-2">
            
            <div class="input-group"><label>ชื่อคอร์สเรียน</label><input type="text" name="title" required></div>
            <div class="input-group"><label>ผู้สอน / แพลตฟอร์ม (เช่น Thai MOOC)</label><input type="text" name="provider" required></div>
            
            <div class="input-group">
                <label>หมวดหมู่</label>
                <select name="category" required>
                    <option value="IT & Programming">IT & Programming</option>
                    <option value="Design & Creative">Design & Creative</option>
                    <option value="Data & Analytics">Data & Analytics</option>
                    <option value="Language">Language (ภาษา)</option>
                    <option value="Business">Business (ธุรกิจ)</option>
                </select>
            </div>
            <div class="input-group">
                <label>ระดับเนื้อหา</label>
                <select name="level" required>
                    <option value="Beginner">เริ่มต้น (Beginner)</option>
                    <option value="Intermediate">ปานกลาง (Intermediate)</option>
                    <option value="Advanced">ขั้นสูง (Advanced)</option>
                </select>
            </div>

            <div class="input-group"><label>ระยะเวลา (เช่น 4 ชั่วโมง, 2 สัปดาห์)</label><input type="text" name="duration"></div>
            <div class="input-group">
                <label>ภาษาที่สอน</label>
                <select name="language" required>
                    <option value="ไทย">ภาษาไทย</option>
                    <option value="English">ภาษาอังกฤษ</option>
                </select>
            </div>

            <div class="input-group">
                <label>ใบประกาศนียบัตร (Certificate)</label>
                <select name="has_cert" required>
                    <option value="1">มีใบประกาศนียบัตรฟรี</option>
                    <option value="0">ไม่มีใบประกาศนียบัตร</option>
                </select>
            </div>
            
            <!-- ช่องลิงก์ต้นทางแสดงเต็มแถวเพื่อให้กรอกและตรวจสอบได้สะดวก -->
            <div class="input-group col-span-full" style="grid-column: 1 / -1;">
                <label>ลิงก์ URL ของคอร์สต้นทาง</label>
                <input type="url" name="course_url" placeholder="https://..." required>
            </div>

            <div class="input-group col-span-full" style="grid-column: 1 / -1;">
                <label>รูปภาพหน้าปก (ไฟล์ .jpg, .png แนะนำสัดส่วน 16:9)</label>
                <input type="file" name="cover_image" accept="image/jpeg, image/png, image/webp" class="bg-gray-light">
            </div>

            <div class="text-right mt-2" style="grid-column: 1 / -1;">
                <button type="submit" name="add_free_course" class="btn w-auto px-5">บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>