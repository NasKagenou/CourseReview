<?php
// ===== เริ่มต้น session และตรวจสิทธิ์ =====
session_start();
require_once '../config/Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// ===== เตรียมคลาสและรับข้อเสนอแนะ =====
require_once '../classes/Feedback.php';
require_once '../classes/Log.php';

$database = new Database();
$db = $database->getConnection();
$feedbackObj = new Feedback($db);
$log = new Log($db);

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    if ($feedbackObj->create($_SESSION['user_id'], $_POST['subject'], $_POST['message'])) {
        $msg = "ส่งข้อความสำเร็จ! ขอบคุณสำหรับความคิดเห็นครับ";
        
        // เมื่อ Feedback::create() สำเร็จ จึงเรียก Log::addLog() บันทึกกิจกรรมของผู้ส่ง
        $log->addLog($_SESSION['user_id'], 'add_feedback', 'ส่งข้อเสนอแนะหัวข้อ: ' . $_POST['subject'], 'student/feedback.php');
    }
}

// ===== แสดงผลตอบกลับและแบบฟอร์ม =====
$page_title = "แจ้งปัญหาและข้อเสนอแนะ - Course Review";
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<div class="container max-w-800 py-5">
    <?php if (!empty($msg)): ?>
        <div class="bg-success-light p-3 rounded-md mb-4 text-center text-sm font-medium">✅ <?php echo $msg; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <h3 class="mb-4 text-dark">แจ้งปัญหาและข้อเสนอแนะ (Feedback)</h3>
        <p class="text-muted mb-4">พบเจอบัค หรือมีไอเดียอยากให้เราเพิ่มฟีเจอร์ไหน แจ้งผู้ดูแลระบบได้เลยครับ!</p>
        
        <form action="feedback.php" method="POST">
            <div class="input-group">
                <label>หัวข้อเรื่อง</label>
                <select name="subject" required>
                    <option value="" disabled selected>- เลือกหัวข้อ -</option>
                    <option value="แจ้งบัค/ปัญหาการใช้งาน">แจ้งบัค / ปัญหาการใช้งาน</option>
                    <option value="เสนอแนะฟีเจอร์ใหม่">เสนอแนะฟีเจอร์ใหม่</option>
                    <option value="ติดต่อผู้ดูแลระบบ">ติดต่อผู้ดูแลระบบ</option>
                    <option value="อื่นๆ">อื่นๆ</option>
                </select>
            </div>
            <div class="input-group">
                <label>รายละเอียด</label>
                <textarea name="message" rows="5" placeholder="อธิบายรายละเอียดปัญหาหรือข้อเสนอแนะของคุณ..." required style="resize: vertical;"></textarea>
            </div>
            <div class="text-right mt-2">
                <button type="submit" name="submit_feedback" class="btn w-auto px-5">ส่งข้อความ</button>
            </div>
        </form>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>