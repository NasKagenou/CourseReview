<?php
session_start();
require_once '../../config/Database.php';

// ===== ตรวจสิทธิ์ผู้ดูแล =====
// ตรวจ session และ role ก่อนอนุญาตให้แก้ไขข้อมูลคอร์สฟรี
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// ===== เตรียมคลาสและอ่านข้อมูลคอร์ส =====
require_once '../../classes/FreeCourse.php';
$database = new Database();
$db = $database->getConnection();
$courseObj = new FreeCourse($db);

// อ่าน id จาก URL แล้วเรียก FreeCourse::readOne() เพื่อโหลดข้อมูลเดิมมาแสดง
$course_id = isset($_GET['id']) ? $_GET['id'] : die('ไม่พบรหัสคอร์ส');
$edit_data = $courseObj->readOne($course_id);
if(!$edit_data) die('ไม่พบข้อมูลคอร์สในระบบ');

$msg_error = "";

// ===== รับข้อมูลฟอร์มและจัดการรูปภาพ =====
// ทำงานส่วนนี้เมื่อได้รับคำขอ POST ที่ส่งมาจากปุ่มอัปเดต
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_free_course'])) {
    
    $cover_image = ""; 
    // รูปเป็นตัวเลือก หากไม่มีไฟล์ใหม่จะคงค่า cover_image ว่างไว้เพื่อให้เมธอดจัดการต่อ
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        // ตรวจนามสกุลไฟล์ตามรายการที่กำหนด ก่อนย้ายไฟล์รูปไปยังโฟลเดอร์เก็บรูป
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        $file_name = $_FILES['cover_image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_name = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $upload_path = "../../assets/uploads/" . $new_name;
            
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                $cover_image = $new_name;
                
                // เมื่อติดตั้งรูปใหม่สำเร็จแล้ว จึงลบไฟล์รูปเดิมถ้ามีอยู่
                if (!empty($edit_data['cover_image'])) {
                    $old_path = "../../assets/uploads/" . $edit_data['cover_image'];
                    if(file_exists($old_path)) unlink($old_path);
                }
            } else {
                $msg_error = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
            }
        } else {
            $msg_error = "รองรับเฉพาะไฟล์รูปภาพเท่านั้น";
        }
    }

    // ถ้าไม่มีข้อผิดพลาด ให้ FreeCourse::update() บันทึกข้อมูลและเปลี่ยนหน้าเมื่อสำเร็จ
    if (empty($msg_error)) {
        if ($courseObj->update($course_id, $_POST['title'], $_POST['provider'], $_POST['category'], $_POST['level'], $_POST['duration'], $_POST['language'], $_POST['has_cert'], $_POST['course_url'], $cover_image)) {
            header("Location: manage_free_courses.php"); 
            exit();
        } else {
            $msg_error = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล";
        }
    }
}

// ===== เตรียมส่วนหัวและแสดงแบบฟอร์ม =====
$page_title = "แก้ไขคอร์สฟรี - Course Review";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>
<!-- แบบฟอร์มแสดงข้อมูลเดิม รับค่าที่แก้ไข และแสดงข้อความผิดพลาดถ้ามี -->
<div class="container max-w-850 py-5">
    <div class="mb-3">
        <a href="manage_free_courses.php" class="text-muted no-underline">⬅️ กลับหน้ารายการคอร์ส</a>
    </div>
    
    <?php if(!empty($msg_error)): ?>
        <div class="bg-danger-light p-3 rounded-md mb-4 text-center text-sm font-medium">❌ <?php echo $msg_error; ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 class="mb-4 text-dark">แก้ไขคอร์สเรียนฟรี</h3>
        
        <form action="edit_free_course.php?id=<?php echo urlencode($course_id); ?>" method="POST" enctype="multipart/form-data" class="form-grid-2">
            
            <div class="input-group"><label>ชื่อคอร์สเรียน</label><input type="text" name="title" value="<?php echo htmlspecialchars($edit_data['title']); ?>" required></div>
            <div class="input-group"><label>ผู้สอน / แพลตฟอร์ม</label><input type="text" name="provider" value="<?php echo htmlspecialchars($edit_data['provider']); ?>" required></div>
            
            <div class="input-group">
                <label>หมวดหมู่</label>
                <select name="category" required>
                    <option value="IT & Programming" <?php if($edit_data['category'] == 'IT & Programming') echo 'selected'; ?>>IT & Programming</option>
                    <option value="Design & Creative" <?php if($edit_data['category'] == 'Design & Creative') echo 'selected'; ?>>Design & Creative</option>
                    <option value="Data & Analytics" <?php if($edit_data['category'] == 'Data & Analytics') echo 'selected'; ?>>Data & Analytics</option>
                    <option value="Language" <?php if($edit_data['category'] == 'Language') echo 'selected'; ?>>Language (ภาษา)</option>
                    <option value="Business" <?php if($edit_data['category'] == 'Business') echo 'selected'; ?>>Business (ธุรกิจ)</option>
                </select>
            </div>
            <div class="input-group">
                <label>ระดับเนื้อหา</label>
                <select name="level" required>
                    <option value="Beginner" <?php if($edit_data['level'] == 'Beginner') echo 'selected'; ?>>เริ่มต้น (Beginner)</option>
                    <option value="Intermediate" <?php if($edit_data['level'] == 'Intermediate') echo 'selected'; ?>>ปานกลาง (Intermediate)</option>
                    <option value="Advanced" <?php if($edit_data['level'] == 'Advanced') echo 'selected'; ?>>ขั้นสูง (Advanced)</option>
                </select>
            </div>

            <div class="input-group"><label>ระยะเวลา</label><input type="text" name="duration" value="<?php echo htmlspecialchars($edit_data['duration']); ?>"></div>
            <div class="input-group">
                <label>ภาษาที่สอน</label>
                <select name="language" required>
                    <option value="ไทย" <?php if($edit_data['language'] == 'ไทย') echo 'selected'; ?>>ภาษาไทย</option>
                    <option value="English" <?php if($edit_data['language'] == 'English') echo 'selected'; ?>>ภาษาอังกฤษ</option>
                </select>
            </div>

            <div class="input-group">
                <label>ใบประกาศนียบัตร</label>
                <select name="has_cert" required>
                    <option value="1" <?php if($edit_data['has_certificate'] == 1) echo 'selected'; ?>>📜 มีใบประกาศนียบัตรฟรี</option>
                    <option value="0" <?php if($edit_data['has_certificate'] == 0) echo 'selected'; ?>>ไม่มีใบประกาศนียบัตร</option>
                </select>
            </div>
            
            <div class="input-group col-span-full" style="grid-column: 1 / -1;">
                <label>ลิงก์ URL ของคอร์สต้นทาง</label>
                <input type="url" name="course_url" value="<?php echo htmlspecialchars($edit_data['course_url']); ?>" required>
            </div>

            <div class="input-group col-span-full" style="grid-column: 1 / -1;">
                <label>อัปโหลดรูปภาพหน้าปกใหม่ (ปล่อยว่างถ้าต้องการใช้รูปเดิม)</label>
                <input type="file" name="cover_image" accept="image/jpeg, image/png, image/webp" class="bg-gray-light mb-2">
                
                <?php if(!empty($edit_data['cover_image'])): ?>
                    <p class="text-sm text-muted">รูปปัจจุบัน:</p>
                    <?php $image_path = $edit_data['cover_image'] === 'no_image.png' ? '../../assets/images/' : '../../assets/uploads/'; ?>
                    <img src="<?php echo htmlspecialchars($image_path . $edit_data['cover_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Current Cover" class="rounded-md mt-2" style="max-height: 100px;">
                <?php endif; ?>
            </div>

            <div class="text-right mt-2" style="grid-column: 1 / -1;">
                <button type="submit" name="update_free_course" class="btn w-auto px-5">อัปเดตข้อมูล</button>
            </div>
        </form>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>