<?php
// ===== เริ่มต้น session และตรวจสิทธิ์ =====
session_start();
require_once '../config/Database.php';

// ต้องล็อกอินก่อนอ่านตาราง และใช้ user_id จาก session เพื่อเลือกข้อมูลของบัญชีนี้
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// ===== อ่านตารางและประมวลผลคำขอ =====
require_once '../classes/Schedule.php';
$database = new Database();
$db = $database->getConnection();
$schedule = new Schedule($db);

$user_id = $_SESSION['user_id'];

// คำขอ remove_course เรียก Schedule::removeCourse() แล้วกลับหน้านี้เพื่อไม่ให้ refresh ลบซ้ำ
if (isset($_GET['remove_course'])) {
    $schedule->removeCourse($user_id, $_GET['remove_course']);
    header("Location: schedule.php?msg=deleted");
    exit();
}

$stmt_courses = $schedule->getMySchedule($user_id);
$all_my_courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

$days_th = ['Monday' => 'จันทร์', 'Tuesday' => 'อังคาร', 'Wednesday' => 'พุธ', 'Thursday' => 'พฤหัสฯ', 'Friday' => 'ศุกร์', 'Saturday' => 'เสาร์', 'Sunday' => 'อาทิตย์'];
$schedule_data = [];
$total_credits = 0;

// Schedule::getMySchedule() คืนวิชาของผู้ใช้; วนรวมหน่วยกิตและแยกวิชาที่มีเวลาไว้ตามวัน
foreach ($all_my_courses as $row) {
    $total_credits += $row['credits'];
    if (!empty($row['teach_day']) && !empty($row['start_time']) && !empty($row['end_time'])) {
        $schedule_data[$row['teach_day']][] = $row;
    }
}

function getCoursePosition($start, $end) {
    // แปลงเวลาเริ่มและจบเป็นชั่วโมงทศนิยม เพื่อคำนวณตำแหน่งกับความกว้างของบล็อกในตาราง
    $s_parts = explode(':', $start);
    $e_parts = explode(':', $end);
    $s_hour = (int)$s_parts[0] + ((int)$s_parts[1] / 60);
    $e_hour = (int)$e_parts[0] + ((int)$e_parts[1] / 60);
    // จำกัดขอบเขตเวลาที่นำไปวางบนกริดให้ตรงกับช่วงเวลาที่แสดงในหน้าตาราง
    if ($s_hour < 8) {
        $s_hour = 8;
    }
    if ($e_hour > 19) {
        $e_hour = 19;
    }
    $left = (($s_hour - 8) / 11) * 100;
    $width = (($e_hour - $s_hour) / 11) * 100;
    return "left: {$left}%; width: {$width}%;";
}

// ===== แสดงสถานะและตารางเรียน =====
$page_title = "ตารางเรียน - Course Review";
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<div class="container py-5">
    
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] == 'deleted'): ?>
            <div class="bg-danger-light p-3 rounded-md mb-4 text-center text-sm font-medium">นำวิชาออกจากตารางเรียบร้อยแล้ว</div>
        <?php elseif($_GET['msg'] == 'success'): ?>
            <div class="bg-success-light p-3 rounded-md mb-4 text-center text-sm font-medium">อัปเดตเวลาเรียนเรียบร้อยแล้ว!</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-5">
        <h2 class="text-dark m-0">ตารางเรียนของฉัน</h2>
        <div class="bg-success-light px-4 py-2 rounded-md font-semibold text-success">
            รวมหน่วยกิตทั้งหมด: <span class="text-xl"><?php echo $total_credits; ?></span>
        </div>
    </div>

    <div class="timetable-wrapper">
        <div class="timetable">
            <div class="time-header">
                <?php for($i=8; $i<19; $i++): ?>
                    <div class="time-slot"><?php echo sprintf("%02d:00", $i); ?></div>
                <?php endfor; ?>
            </div>
            <?php foreach ($days_th as $en_day => $th_day): ?>
                <div class="day-row">
                    <div class="day-label"><?php echo $th_day; ?></div>
                    <div class="grid-bg">
                        <?php if (isset($schedule_data[$en_day])): ?>
                            <?php foreach ($schedule_data[$en_day] as $c): ?>
                                <div class="course-block" style="<?php echo getCoursePosition($c['start_time'], $c['end_time']); ?>" title="<?php echo htmlspecialchars($c['course_name']); ?> (<?php echo date('H:i', strtotime($c['start_time'])) . ' - ' . date('H:i', strtotime($c['end_time'])); ?>)">
                                    <strong><?php echo htmlspecialchars($c['course_id'] ); ?></strong>
                                    <span><?php echo date('H:i', strtotime($c['start_time'])) . '-' . date('H:i', strtotime($c['end_time'])); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h3 class="text-primary mb-4 pb-2 border-b-primary">⚙ จัดการเวลาเรียนและรายวิชา</h3>
        
        <!-- รายการนี้เปิด edit_schedule.php เพื่อปรับวันเวลา หรือส่งคำขอ remove_course เพื่อนำวิชาออก -->
        <?php if (!empty($all_my_courses)): ?>
            <div style="overflow-x: auto;">
                <table class="table" style="min-width: 700px;">
                    <thead>
                        <tr>
                            <th style="width: 15%;">รหัสวิชา</th>
                            <th style="width: 35%;">ชื่อวิชา</th>
                            <th style="width: 25%;">วัน-เวลาเรียน</th>
                            <th style="width: 25%; text-align: center;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_my_courses as $s): ?>
                        <tr>
                            <td><strong class="text-primary"><?php echo htmlspecialchars($s['course_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($s['course_name']); ?></td>
                            <td>
                                <?php if (!empty($s['teach_day'])): ?>
                                    <span class="badge badge-success"><?php echo $days_th[$s['teach_day']]; ?></span>
                                    <div class="text-xs text-muted mt-1">
                                        <?php echo date('H:i', strtotime($s['start_time'])) . ' - ' . date('H:i', strtotime($s['end_time'])) . ' น.'; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-warning text-sm font-medium">ยังไม่ตั้งเวลา</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="edit_schedule.php?id=<?php echo urlencode($s['course_id']); ?>" class="text-warning text-sm font-medium no-underline mr-3">✏️ ตั้งเวลา</a>
                                <a href="schedule.php?remove_course=<?php echo urlencode($s['course_id']); ?>" class="text-danger text-sm font-medium no-underline" onclick="return confirm('นำวิชานี้ออกจากตารางเรียนใช่หรือไม่?');">🗑️ ลบ</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center p-5">
                <p class="text-muted mb-3">คุณยังไม่มีวิชาในตารางเรียนเลย</p>
                <a href="../index.php" class="btn w-auto px-4">กลับไปค้นหาวิชา</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>