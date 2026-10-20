<?php
// ===== เริ่มต้น session และตรวจเจ้าของตาราง =====
session_start();
require_once '../config/Database.php';

// ต้องล็อกอินก่อน และใช้ user_id จาก session กับการอ่านหรือแก้ไขตารางของเจ้าของบัญชี
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// ===== เตรียมข้อมูลและบันทึกเวลาเรียน =====
require_once '../classes/Schedule.php';
$database = new Database();
$db = $database->getConnection();
$schedule = new Schedule($db);

$user_id = $_SESSION['user_id'];
$course_id = isset($_GET['id']) ? $_GET['id'] : die('ไม่พบรหัสวิชา');

// เมื่อส่งฟอร์ม ให้ Schedule::updateTime() บันทึกวันและเวลา แล้วกลับ schedule.php พร้อมข้อความสำเร็จ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_time'])) {
    $schedule->updateTime($user_id, $_POST['course_id'], $_POST['teach_day'], $_POST['start_time'], $_POST['end_time']);
    header("Location: schedule.php?msg=success");
    exit();
}

$edit_data = $schedule->getScheduleOne($user_id, $course_id);
// Schedule::getScheduleOne() อ่านรายการของผู้ใช้นี้; หากไม่พบข้อมูลให้หยุดก่อนแสดงแบบฟอร์ม
if (!$edit_data) {
    die('ไม่พบข้อมูลวิชานี้ในตารางของคุณ');
}

// ===== แสดงข้อมูลวิชาและแบบฟอร์ม =====
$page_title = "ตั้งเวลาเรียน - Course Review";
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<div class="container max-w-800 py-5">
    <div class="mb-3">
        <a href="schedule.php" class="text-muted no-underline">⬅️ กลับหน้าตารางเรียน</a>
    </div>
    <div class="card">
        <h3 class="mb-4 text-dark">ตั้งเวลาเรียนวิชา: <span class="text-primary"><?php echo htmlspecialchars($edit_data['course_id']); ?></span></h3>
        <p class="text-muted text-lg mb-4">ชื่อวิชา: <?php echo htmlspecialchars($edit_data['course_name']); ?></p>
        
        <form action="edit_schedule.php?id=<?php echo urlencode($course_id); ?>" method="POST" class="form-grid-2">
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($edit_data['course_id']); ?>">
            
            <div class="input-group" style="grid-column: 1 / -1;">
                <label>วันเรียน</label>
                <select name="teach_day" required>
                    <option value="">- เลือกวัน -</option>
                    <?php 
                    $days = [
                        'Monday' => 'วันจันทร์',
                        'Tuesday' => 'วันอังคาร',
                        'Wednesday' => 'วันพุธ',
                        'Thursday' => 'วันพฤหัสบดี',
                        'Friday' => 'วันศุกร์',
                        'Saturday' => 'วันเสาร์',
                        'Sunday' => 'วันอาทิตย์'
                    ];
                    foreach ($days as $en => $th): 
                    ?>
                        <option value="<?php echo $en; ?>" <?php echo ($edit_data['teach_day'] == $en) ? 'selected' : ''; ?>><?php echo $th; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group"><label>เวลาเริ่ม</label><input type="time" name="start_time" value="<?php echo $edit_data['start_time']; ?>" required></div>
            <div class="input-group"><label>เวลาจบ</label><input type="time" name="end_time" value="<?php echo $edit_data['end_time']; ?>" required></div>
            
            <div class="text-right mt-2" style="grid-column: 1 / -1;">
                <button type="submit" name="update_time" class="btn w-auto px-4">บันทึกเวลาเรียน</button>
            </div>
        </form>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>