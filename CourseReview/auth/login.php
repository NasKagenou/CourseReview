<?php
// ===== เริ่มต้น session และตรวจสถานะการเข้าสู่ระบบ =====
session_start();
require_once '../config/Database.php';

// ถ้ามี session อยู่แล้ว ให้ส่งผู้ดูแลไปหน้าแอดมินหรือผู้ใช้อื่นไปหน้าหลัก
if (isset($_SESSION['user_id'])) { 
    if ($_SESSION['role'] === 'admin') {
        header("Location: " . BASE_URL . "admin/index.php");
    } else {
        header("Location: " . BASE_URL . "index.php");
    }
    exit(); 
}

// ===== ตรวจข้อมูลเข้าสู่ระบบ =====
require_once '../classes/User.php';
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // ต้องกรอกทั้งสองช่องก่อนเรียก User::login() เพื่อให้คลาสตรวจสอบบัญชีกับฐานข้อมูล
    if (!empty($username) && !empty($password)) {
        $logged_in_user = $user->login($username, $password);
        if ($logged_in_user) {
            // บันทึกข้อมูลบัญชีใน session ให้หน้าอื่นใช้ระบุตัวผู้ใช้และบทบาท
            $_SESSION['user_id'] = $logged_in_user['user_id'];
            $_SESSION['username'] = $logged_in_user['username'];
            $_SESSION['role'] = $logged_in_user['role'];
            
            // ส่งต่อไปหน้าปลายทางตาม role; exit() หยุดการแสดงหน้าเข้าสู่ระบบ
            if ($_SESSION['role'] === 'admin') {
                header("Location: " . BASE_URL . "admin/index.php");
            } else {
                header("Location: " . BASE_URL . "index.php"); 
            }
            exit();
        } else { 
            $error_message = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง"; 
        }
    } else { 
        $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน"; 
    }
}

// ===== แสดงฟอร์มและข้อความแจ้งผล =====
$page_title = "เข้าสู่ระบบ - Course Review";
require_once '../includes/header.php';
?>
<div class="container flex flex-col items-center justify-center py-5">
    <div class="auth-layout mx-auto">
        <div class="p-4">
            <h1 class="text-primary text-4xl mb-3 font-bold">CourseReview.</h1>
            <h3 class="text-dark mb-4 text-2xl">บอกลาการสุ่มเลือกลงทะเบียนเรียน</h3>
            <p class="text-muted leading-loose text-sm m-0">ระบบของเราคือ <strong>ศูนย์กลางรวบรวมข้อมูลจากรุ่นพี่สู่รุ่นน้อง</strong> ที่จะช่วยให้คุณเลือกลงเรียนวิชาที่ตรงสไตล์ตัวเอง รู้เกณฑ์ให้คะแนนล่วงหน้า และลดความเสี่ยงในการดรอปเรียน!</p>
        </div>
        <div class="card auth-card max-w-420 mx-auto">
            <h2 class="text-primary mb-5 text-center">เข้าสู่ระบบ</h2>
            <?php if (!empty($error_message)): ?>
                <div class="bg-danger-light p-3 rounded-md mb-4 text-sm text-center">❌ <?php echo $error_message; ?></div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <div class="input-group">
                    <label>ชื่อผู้ใช้ (Username)</label>
                    <input type="text" name="username" placeholder="กรอกชื่อผู้ใช้ของคุณ" required>
                </div>
                <div class="input-group">
                    <label>รหัสผ่าน (Password)</label>
                    <input type="password" name="password" placeholder="กรอกรหัสผ่านของคุณ" required>
                </div>
                <button type="submit" name="login" class="btn mt-3 p-3">เข้าสู่ระบบ</button>
            </form>
            <p class="mt-5 text-sm text-muted text-center">
                ยังไม่มีบัญชีใช่ไหม? <a href="register.php" class="text-primary font-semibold no-underline">สมัครสมาชิกที่นี่</a>
            </p>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>