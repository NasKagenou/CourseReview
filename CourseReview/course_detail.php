<?php
// ===== เริ่มต้น session และตรวจสิทธิ์ =====
session_start();
date_default_timezone_set('Asia/Bangkok');
require_once 'config/Database.php';

// ต้องเข้าสู่ระบบก่อนดูรายละเอียดวิชา หรือส่งคำขอรีวิวและตอบกลับ
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// ===== เตรียมคลาสและรับรหัสวิชา =====
require_once 'classes/Review.php';
require_once 'classes/Schedule.php';

$database = new Database();
$db = $database->getConnection();

$review = new Review($db);
$schedule = new Schedule($db);

$course_id = isset($_GET['id']) ? $_GET['id'] : die('ไม่พบรหัสวิชา');
$msg_schedule = "";

// เฉพาะนักศึกษาที่เพิ่มวิชาลงตารางได้; คำขอจากแอดมินจะไม่ถูกบันทึก
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_schedule']) && $_SESSION['role'] == 'student') {
    $result = $schedule->addCourse($_SESSION['user_id'], $course_id);
    if ($result === "exists") {
        $msg_schedule = "คุณมีวิชานี้ในที่บันทึกไว้แล้ว";
    } else if ($result === "success") {
        $msg_schedule = "บันทึกวิชาลงในโปรไฟล์สำเร็จ!";
    }
}

$is_saved = false;
if ($_SESSION['role'] == 'student') {
    // Schedule::getScheduleOne() เช็กว่าผู้ใช้บันทึกวิชานี้แล้วหรือไม่
    if ($schedule->getScheduleOne($_SESSION['user_id'], $course_id)) { 
        $is_saved = true; 
    }
}

// ===== จัดการคำขอจากแบบฟอร์มและผู้ดูแล =====
// รับคำสั่งลบเฉพาะเมื่อ role เป็น admin แล้วลบบทวิจารณ์ที่ระบุ
if (isset($_GET['delete_review_id']) && $_SESSION['role'] == 'admin' ){
    $review_id_to_delete = $_GET['delete_review_id'];
    $review->delete($review_id_to_delete);
    
    // Log::addLog() บันทึกกิจกรรมก่อน redirect กลับวิชาเดิมและหยุดประมวลผล
    require_once 'classes/Log.php';
    $log = new Log($db);
    $log->addLog($_SESSION['user_id'], 'delete_review', 'แอดมินลบรีวิวรหัส: ' . $review_id_to_delete . ' ในวิชา: ' . $course_id, 'course_detail.php?id=' . urlencode($course_id));
    
    header("Location: course_detail.php?id=" . urlencode($course_id)); 
    exit();
}

// นักศึกษาและแอดมินเขียนรีวิวได้; หลังบันทึกจะเก็บ review_id ใน Log และเปิดตรงรีวิวใหม่
if (
    $_SERVER['REQUEST_METHOD'] == 'POST'
    && isset($_POST['submit_review'])
    && in_array($_SESSION['role'], ['student', 'admin'], true)
) {
    if ($review->create($_SESSION['user_id'], $course_id, $_POST['rating'], $_POST['comment'])) {
        require_once 'classes/Log.php';
        $log = new Log($db);
        $review_id = (int)$db->lastInsertId();
        $log->addLog(
            $_SESSION['user_id'],
            'add_review',
            'เขียนรีวิวและให้ดาวในวิชา: ' . $course_id,
            'course_detail.php?id=' . urlencode($course_id) . '#review-' . $review_id
        );
        header("Location: course_detail.php?id=" . urlencode($course_id) . '#review-' . $review_id); exit();
    }
}

// ปุ่มโหวตเรียก Review::upvote() แล้ว redirect กลับมาแสดงจำนวนล่าสุด
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upvote_btn'])) {
    $review->upvote($_POST['review_id'], $_SESSION['user_id']);
    header("Location: course_detail.php?id=" . urlencode($course_id)); exit();
}

// ฟอร์มตอบกลับเรียก Review::addReply() และบันทึก review_id ใน Log
// จากนั้นกลับมาที่การ์ดรีวิวต้นฉบับ เพื่อให้เห็นคำตอบที่เพิ่งส่ง
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_reply'])) {
    $review_id = (int)$_POST['review_id'];
    $review->addReply($review_id, $_SESSION['user_id'], trim($_POST['reply_text']));
    require_once 'classes/Log.php';
    $log = new Log($db);
    $log->addLog(
        $_SESSION['user_id'],
        'add_reply',
        'ตอบกลับความเห็นในวิชา: ' . $course_id,
        'course_detail.php?id=' . urlencode($course_id) . '#review-' . $review_id
    );
    header("Location: course_detail.php?id=" . urlencode($course_id) . '#review-' . $review_id); exit();
}

// ===== โหลดข้อมูลสำหรับแสดงหน้า =====
// หลังจัดการคำขอ ใช้ Course::readOne() และ Review::readByCourse() อ่านรายละเอียดกับรีวิว
require_once 'classes/Course.php';
$courseObj = new Course($db);
$course_data = $courseObj->readOne($course_id);

$stmt_reviews = $review->readByCourse($course_id);

$page_title = "รายละเอียดวิชา - Course Review";
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>
<div class="container max-w-850 py-5">
    <div class="card text-center mb-5">
        <h3 class="text-muted font-medium mb-1"><?php echo htmlspecialchars($course_id); ?></h3>
        <h2 class="text-primary text-3xl mb-3"><?php echo htmlspecialchars($course_data['course_name']); ?></h2>
        
        <div class="course-meta bg-primary-light text-primary mb-3 text-sm">
            อาจารย์ผู้สอน: <?php echo htmlspecialchars($course_data['teacher_name']); ?> 
            <span class="text-muted mx-auto px-3">|</span> 
            หน่วยกิต: <?php echo htmlspecialchars($course_data['credits']); ?>
        </div>

        <?php if ($_SESSION['role'] == 'student'): ?>
            <div class="mt-2">
                <?php if ($is_saved): ?>    
                    <div class="flex justify-center gap-2">
                        <span class="btn btn-disabled w-auto px-4 text-sm">บันทึกวิชานี้แล้ว</span>
                        <a href="student/schedule.php" class="btn-outline w-auto px-4 text-sm">ไปหน้าตารางเรียน</a>
                    </div>
                <?php else: ?>
                    <form action="course_detail.php?id=<?php echo urlencode($course_id); ?>" method="POST" class="m-0">
                        <button type="submit" name="add_schedule" class="btn btn-success w-auto px-4 text-sm">บันทึกเก็บไว้จัดตาราง</button>
                    </form>
                <?php endif; ?>
                <?php if (!empty($msg_schedule)): ?>
                    <div class="mt-2 text-sm font-medium text-success"><?php echo $msg_schedule; ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- นักศึกษาและแอดมินเขียนรีวิวได้; ปุ่มบันทึกวิชาแสดงเฉพาะนักศึกษาด้านบน -->
    <?php if (in_array($_SESSION['role'], ['student', 'admin'], true)): ?>
    <div class="card mb-5">
        <h3 class="mb-4">✍ เขียนรีวิววิชานี้</h3>
        <form action="course_detail.php?id=<?php echo urlencode($course_id); ?>" method="POST">
            <div class="input-group">
                <label>ให้คะแนนดาว (1-5)</label>
                <!-- แบบฟอร์มส่งคะแนนและข้อความด้วย POST; ส่วนประมวลผลเรียก Review::create() -->
                <select name="rating" required>
                    <option value="5">5 ดาว - ดีเยี่ยม</option>
                    <option value="4">4 ดาว - ดี</option>
                    <option value="3">3 ดาว - ปานกลาง</option>
                    <option value="2">2 ดาว - พอใช้</option>
                    <option value="1">1 ดาว - ต้องปรับปรุง</option>
                </select>
            </div>
            <div class="input-group">
                <label>แชร์ประสบการณ์ของคุณ</label>
                <textarea name="comment" rows="4" placeholder="เนื้อหาวิชา, การสอนของอาจารย์, งานกลุ่ม ฯลฯ" required></textarea>
            </div>
            <div class="text-right">
                <button type="submit" name="submit_review" class="btn w-auto px-4">ส่งรีวิว</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <h3 class="mb-4 flex items-center gap-2">
        💬 รีวิวจากเพื่อนนักศึกษา <span class="badge m-0 text-sm"><?php echo $stmt_reviews->rowCount(); ?> รีวิว</span>
    </h3>
    
    <?php if ($stmt_reviews->rowCount() > 0): while ($row = $stmt_reviews->fetch(PDO::FETCH_ASSOC)): ?>
        <!-- รหัสจุดยึดนี้ใช้ให้ลิงก์จาก Log เลื่อนมาที่การ์ดรีวิวนี้โดยตรง -->
        <div class="review-box" id="review-<?php echo (int)$row['review_id']; ?>">
            <div class="flex justify-between items-center mb-3">
                <strong class="text-dark text-lg">👤 <?php echo htmlspecialchars($row['username']); ?></strong>
                <div class="flex items-center gap-3">
                    <!-- แสดงค่าคะแนนเป็นดาวเต็มและดาวโปร่งให้ครบห้าดวง -->
                    <span class="stars"><?php echo str_repeat('★', $row['rating']) . str_repeat('☆', 5 - $row['rating']); ?></span>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <a href="course_detail.php?id=<?php echo urlencode($course_id); ?>&delete_review_id=<?php echo $row['review_id']; ?>" 
                           class="bg-danger-light text-xs no-underline px-3 py-2 rounded-md"
                           onclick="return confirm('แอดมินต้องการลบรีวิวนี้ใช่หรือไม่?');">🗑 ลบ</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <p class="text-sm text-muted leading-relaxed">
                <?php echo nl2br(htmlspecialchars($row['comment'])); ?>
            </p>
            
            <!-- ปุ่มส่ง review_id กลับให้ส่วนประมวลผลเรียกเมธอดตอบกลับหรือโหวต -->
            <div class="review-footer">
                <div class="text-xs text-muted"><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></div>
                <div class="flex gap-2">
                    <button type="button" onclick="document.getElementById('reply-form-<?php echo $row['review_id']; ?>').classList.toggle('hidden')" class="btn-outline px-3 py-2 text-xs">💬 ตอบกลับ</button>
                    <form action="course_detail.php?id=<?php echo urlencode($course_id); ?>" method="POST" class="m-0">
                        <input type="hidden" name="review_id" value="<?php echo $row['review_id']; ?>">
                        <button type="submit" name="upvote_btn" class="btn-outline px-3 py-2 text-xs">👍 มีประโยชน์ (<?php echo $row['upvotes']; ?>)</button>
                    </form>
                </div>
            </div>

            <!-- Review::getReplies() โหลดคำตอบที่เชื่อมกับรีวิวนี้เพื่อแสดงใต้ข้อความต้นฉบับ -->
            <?php 
            $stmt_replies = $review->getReplies($row['review_id']);
            if ($stmt_replies->rowCount() > 0): 
            ?>
                <div class="reply-thread mt-3">
                    <?php while ($reply = $stmt_replies->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="reply-item">
                            <strong class="text-dark text-sm">👤 <?php echo htmlspecialchars($reply['username']); ?> 
                                <?php if ($reply['role'] == 'admin') echo '<span class="text-danger text-xs">(แอดมิน)</span>'; ?>
                            </strong>
                            <span class="text-muted text-xs ml-2"><?php echo date('d M Y H:i', strtotime($reply['created_at'])); ?></span>
                            <p class="mt-1 mb-0 text-sm text-muted"><?php echo nl2br(htmlspecialchars($reply['reply_text'])); ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

            <!-- แบบฟอร์มตอบกลับเริ่มต้นแบบซ่อน และปุ่มด้านบนใช้ JavaScript สลับ class hidden -->
            <div id="reply-form-<?php echo $row['review_id']; ?>" class="mt-3 hidden">
                <form action="course_detail.php?id=<?php echo urlencode($course_id); ?>" method="POST" class="m-0 bg-gray-light p-4 rounded-md">
                    <input type="hidden" name="review_id" value="<?php echo $row['review_id']; ?>">
                    
                    <div class="input-group mb-3">
                        <!-- ข้อความจากช่องนี้ถูกส่งไปยัง Review::addReply() เมื่อกดส่ง -->
                        <textarea name="reply_text" rows="2" placeholder="เขียนการตอบกลับของคุณที่นี่..." required style="resize: vertical;"></textarea>
                    </div>
                    
                    <div class="text-right">
                        <button type="button" onclick="document.getElementById('reply-form-<?php echo $row['review_id']; ?>').classList.add('hidden')" class="btn-outline w-auto px-4 py-2 text-sm mr-2">ยกเลิก</button>
                        <button type="submit" name="submit_reply" class="btn w-auto px-4 py-2 text-sm">ส่งข้อความ</button>
                    </div>
                </form>
            </div>

        </div>
    <?php endwhile; else: ?>
        <div class="card text-center py-5"><p class="text-muted text-lg m-0">ยังไม่มีรีวิวสำหรับวิชานี้ เป็นคนแรกที่รีวิวเลย!</p></div>
    <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>