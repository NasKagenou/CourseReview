<?php
// ===== เริ่มต้น session และป้องกันการสมัครซ้ำ =====
session_start();
require_once '../config/Database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// ===== ตรวจข้อมูลและสร้างบัญชี =====
require_once '../classes/User.php';
$error_message = "";
$success_message = "";

// ประมวลผลเฉพาะคำขอ POST ที่มาจากปุ่มสมัครสมาชิก
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // ตรวจช่องที่ต้องกรอกและรหัสยืนยันก่อนเรียก User::register() เพื่อสร้างบัญชี
    if (!empty($username) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            $result = $user->register($username, $password);
            if ($result === "exists") { $error_message = "ชื่อผู้ใช้นี้มีในระบบแล้ว กรุณาใช้ชื่ออื่น"; } 
            elseif ($result === "success") { $success_message = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ"; } 
            else { $error_message = "เกิดข้อผิดพลาดในการสมัครสมาชิก"; }
        } else { $error_message = "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน"; }
    } else { $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน"; }
}
$page_title = "สมัครสมาชิก - Course Review";
require_once '../includes/header.php';
?>
<div class="container flex flex-col items-center justify-center py-5">
    <div class="auth-layout mx-auto">
        <div class="p-4">
            <h1 class="text-primary text-4xl mb-3 font-bold">CourseReview.</h1>
            <h3 class="text-dark mb-4 text-2xl">เริ่มต้นแบ่งปันประสบการณ์</h3>
            <p class="text-muted leading-loose text-sm m-0">มาร่วมเป็นส่วนหนึ่งในคอมมูนิตี้ของเรา! สมัครสมาชิกวันนี้เพื่อเริ่มเขียนรีวิวรายวิชาที่คุณเคยเรียน หรืออ่านเกร็ดความรู้จากรุ่นพี่ เพื่อเตรียมตัวให้พร้อมก่อนลงทะเบียนเรียนในเทอมถัดไป</p>
        </div>
        <div class="card auth-card max-w-420 mx-auto">
            <h2 class="text-primary mb-5 text-center">สมัครสมาชิก</h2>
            <?php if (!empty($error_message)): ?>
                <div class="bg-danger-light p-3 rounded-md mb-4 text-sm text-center">❌ <?php echo $error_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="bg-success-light p-3 rounded-md mb-4 text-sm text-center">✅ <?php echo $success_message; ?> <br><a href="login.php" class="text-success font-semibold">คลิกเพื่อเข้าสู่ระบบ</a></div>
            <?php endif; ?>
            <form action="register.php" method="POST">
                <div class="input-group">
                    <label>ชื่อผู้ใช้ (Username)</label>
                    <input type="text" name="username" placeholder="ตั้งชื่อ Username" required>
                </div>
                <div class="input-group">
                    <label>รหัสผ่าน (Password)</label>
                    <input type="password" name="password" placeholder="ตั้งรหัสผ่าน" required>
                </div>
                <div class="input-group">
                    <label>ยืนยันรหัสผ่าน</label>
                    <input type="password" name="confirm_password" placeholder="กรอกรหัสผ่านอีกครั้ง" required>
                </div>
                <button type="submit" name="register" class="btn mt-3 p-3">ยืนยันการสมัคร</button>
            </form>
            <p class="mt-5 text-sm text-muted text-center">
                มีบัญชีอยู่แล้ว? <a href="login.php" class="text-primary font-semibold no-underline">เข้าสู่ระบบที่นี่</a>
            </p>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>