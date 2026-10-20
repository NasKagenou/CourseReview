<?php
// ===== เริ่มต้น session และตรวจสิทธิ์ =====
session_start();
require_once '../config/Database.php';

// ต้องล็อกอินก่อน จึงใช้ user_id ใน session ระบุบัญชีที่จะเปลี่ยนรหัสผ่านได้
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ===== เตรียมคลาสและประมวลผลแบบฟอร์ม =====
require_once '../classes/User.php';
$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$error_message = "";

// อ่านค่าจาก POST เฉพาะเมื่อส่งแบบฟอร์มเปลี่ยนรหัสผ่าน
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // ตรวจช่องที่ต้องกรอกและการยืนยันรหัสใหม่ ก่อนให้ User::changePassword() ตรวจรหัสเดิมและบันทึก
    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
        if ($new_password === $confirm_password) {
            if ($user->changePassword($_SESSION['user_id'], $current_password, $new_password)) {
                header("Location: ../student/profile.php?tab=settings&msg=pwd_success"); exit();
            } else { $error_message = "รหัสผ่านเดิมไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง"; }
        } else { $error_message = "รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน"; }
    } else { $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน"; }
}

// ===== แสดงแบบฟอร์มและผลการตรวจสอบ =====
$page_title = "เปลี่ยนรหัสผ่าน - Course Review";
require_once '../includes/header.php';
?>
<div class="container flex flex-col items-center justify-center min-h-80vh py-5">
    <div class="card w-full max-w-450">
        <h2 class="text-primary mb-4 text-center">🔑 เปลี่ยนรหัสผ่าน</h2>
        <?php if (!empty($error_message)): ?>
            <div class="bg-danger-light p-3 rounded-md mb-4 text-sm text-center">❌ <?php echo $error_message; ?></div>
        <?php endif; ?>
        <form action="change_password.php" method="POST">
            <div class="input-group">
                <label>รหัสผ่านเดิม</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="input-group">
                <label>รหัสผ่านใหม่</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="input-group">
                <label>ยืนยันรหัสผ่านใหม่</label>
                <input type="password" name="confirm_password" required>
            </div>
            <div class="mt-5 flex flex-col gap-2">
                <button type="submit" name="change_password" class="btn">บันทึกรหัสผ่านใหม่</button>
                <a href="../student/profile.php?tab=settings" class="btn-outline text-center">ยกเลิก / กลับไปหน้าโปรไฟล์</a>
            </div>
        </form>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>