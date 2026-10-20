<?php
// ===== เริ่มต้น session และตรวจสิทธิ์ =====
session_start();
require_once '../config/Database.php';

// ตรวจการเข้าสู่ระบบก่อนอ่านข้อมูลส่วนตัวของเจ้าของ user_id ใน session
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// ===== เตรียมข้อมูลโปรไฟล์และเลือกแท็บ =====
require_once '../classes/User.php';
require_once '../classes/Review.php';
require_once '../classes/Schedule.php';
require_once '../classes/Bookmark.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$review = new Review($db);
$schedule = new Schedule($db);
$bookmark = new Bookmark($db);

$user_id = $_SESSION['user_id'];
// query parameter tab เลือกส่วนของโปรไฟล์; ค่าเริ่มต้นคือประวัติรีวิว
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'reviews'; 
$msg = ""; $msg_type = "";

if (isset($_GET['msg']) && $_GET['msg'] == 'pwd_success') {
    $msg = "เปลี่ยนรหัสผ่านสำเร็จ!";
    $msg_type = "success";
}

// ===== แสดงข้อมูลบัญชีและเมนูแท็บ =====
$page_title = "โปรไฟล์ส่วนตัว - Course Review";
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<div class="container max-w-1000 py-5">
    <div class="profile-layout">
        
        <div class="card p-4">
            <div class="text-center mb-5">
                <div class="avatar-lg"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                <h3 class="text-dark text-xl"><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
                <span class="badge mt-2"><?php echo $_SESSION['role'] == 'admin' ? 'ผู้ดูแลระบบ' : 'นักศึกษา'; ?></span>
            </div>
            
            <div class="flex flex-col gap-2">
                <a href="?tab=reviews" class="btn-outline menu-link <?php echo $active_tab == 'reviews' ? 'active' : ''; ?>">ประวัติการรีวิว</a>
                <a href="?tab=saved_courses" class="btn-outline menu-link <?php echo $active_tab == 'saved_courses' ? 'active' : ''; ?>">วิชาที่บันทึกไว้</a>
                <!-- ลิงก์ในเมนูเปลี่ยนค่า tab เพื่อเลือกเนื้อหาที่จะแสดงในพื้นที่ด้านข้าง -->
                <a href="?tab=free_courses" class="btn-outline menu-link <?php echo $active_tab == 'free_courses' ? 'active' : ''; ?>">คอร์สฟรีที่บันทึกไว้</a>
                <a href="?tab=settings" class="btn-outline menu-link <?php echo $active_tab == 'settings' ? 'active' : ''; ?>">ข้อมูลบัญชี</a>
            </div>
        </div>

        <div class="card min-h-450">
            <?php if (!empty($msg)): ?>
                <div class="<?php echo $msg_type == 'success' ? 'bg-success-light' : 'bg-danger-light'; ?> p-3 rounded-md mb-4 text-center text-sm font-medium">
                    <?php echo $msg_type == 'success' ? '✅ ' : '❌ '; echo $msg; ?>
                </div>
            <?php endif; ?>

            <?php if ($active_tab == 'reviews'): ?>
                <!-- ส่วนประวัติเรียก Review::readByUserId() และแสดงรีวิวของบัญชีนี้ทีละรายการ -->
                <?php $my_reviews = $review->readByUserId($user_id); ?>

                <h3 class="text-primary mb-4 pb-2 border-b-primary">ประวัติการเขียนรีวิว <?php echo $my_reviews->rowCount(); ?> รายการ</h3>
                <?php 
                $my_reviews = $review->readByUserId($user_id);
                if ($my_reviews->rowCount() > 0): 
                    while ($r = $my_reviews->fetch(PDO::FETCH_ASSOC)):
                ?>
                    <div class="review-box">
                        <div class="flex justify-between mb-2">
                            <a href="../course_detail.php?id=<?php echo urlencode($r['course_id']); ?>#review-<?php echo (int)$r['review_id']; ?>" class="text-dark font-semibold no-underline text-lg">
                                <?php echo htmlspecialchars($r['course_id'] . ' - ' . $r['course_name']); ?>
                            </a>
                            <span class="stars"><?php echo str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']); ?></span>
                        </div>
                        <p class="text-sm text-muted leading-relaxed mb-2">"<?php echo htmlspecialchars($r['comment']); ?>"</p>
                        <div class="text-xs text-muted">โพสต์เมื่อ: <?php echo date('d M Y H:i', strtotime($r['created_at'])); ?></div>
                    </div>
                <?php endwhile; else: ?>
                    <p class="text-muted text-center mt-5">คุณยังไม่เคยเขียนรีวิววิชาใดเลย</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($active_tab == 'saved_courses'): ?>
                <!-- ส่วนวิชาที่บันทึกใช้ Schedule::getMySchedule() และลิงก์ต่อไปหน้าจัดตารางเรียน -->
                <h3 class="text-primary mb-4 pb-2 border-b-primary">วิชาที่รอจัดตารางเรียน</h3>
                <?php 
                $my_saved = $schedule->getMySchedule($user_id);
                if ($my_saved->rowCount() > 0): 
                ?>
                    <div style="overflow-x: auto;">
                        <table class="table mb-4 w-full">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">รหัสวิชา</th>
                                    <th>ชื่อวิชา</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($s = $my_saved->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><strong class="text-primary"><?php echo htmlspecialchars($s['course_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($s['course_name']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-right">
                        <a href="schedule.php" class="btn btn-success w-auto px-4">ไปหน้าจัดการตารางเรียนจำลอง</a>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mt-5">ยังไม่มีวิชาที่บันทึกไว้ ลองไปค้นหาวิชาแล้วกดบันทึกดูสิ!</p>
                <?php endif; ?>
            <?php endif; ?>

            <!-- ส่วนคอร์สฟรีเรียก Bookmark::getMyBookmarks() และแสดงรายการที่บันทึกไว้ -->
            <?php if ($active_tab == 'free_courses'): ?>
                <h3 class="text-primary mb-4 pb-2 border-b-primary">คอร์สฟรีที่บันทึกไว้ (Wishlist)</h3>
                <?php 
                $my_bookmarks = $bookmark->getMyBookmarks($user_id);
                if ($my_bookmarks->rowCount() > 0): 
                ?>
                    <div style="overflow-x: auto;">
                        <table class="table mb-4 w-full">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">ผู้สอน/แพลตฟอร์ม</th>
                                    <th style="width: 45%;">ชื่อคอร์ส</th>
                                    <th style="width: 15%;">หมวดหมู่</th>
                                    <th style="width: 20%; text-align: center;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($fc = $my_bookmarks->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><strong class="text-dark"><?php echo htmlspecialchars($fc['provider']); ?></strong></td>
                                    <td>
                                        <div class="font-medium text-primary"><?php echo htmlspecialchars($fc['title']); ?></div>
                                        <?php if ($fc['has_certificate'] == 1): ?>
                                            <span class="text-success text-xs font-bold">มีใบประกาศนียบัตร</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge"><?php echo htmlspecialchars($fc['category']); ?></span></td>
                                    <td class="text-center">
                                        <a href="<?php echo htmlspecialchars($fc['course_url']); ?>" target="_blank" class="btn-outline px-3 py-2 text-xs no-underline">ไปเรียน</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mt-5">คุณยังไม่ได้บันทึกคอร์สฟรีใดๆ เลย <br><a href="../course_free.php" class="text-primary font-medium mt-2 inline-block">ไปค้นหาคอร์สที่สนใจกันเลย!</a></p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($active_tab == 'settings'): ?>
                <!-- แสดงข้อมูลบัญชีจาก session; ลิงก์เปลี่ยนรหัสผ่านไป auth/change_password.php -->
                <h3 class="text-primary mb-4 pb-2 border-b-primary">ข้อมูลบัญชีผู้ใช้</h3>
                <div class="account-grid mb-5">
                    <div class="card bg-gray-light p-3">
                        <p class="text-sm text-muted mb-1">รหัสประจำตัว (User ID)</p>
                        <strong class="text-dark text-lg">#<?php echo str_pad($_SESSION['user_id'], 4, '0', STR_PAD_LEFT); ?></strong>
                    </div>
                    <div class="card bg-gray-light p-3">
                        <p class="text-sm text-muted mb-1">ชื่อผู้ใช้ (Username)</p>
                        <strong class="text-dark text-lg"><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                    </div>
                    <div class="card bg-gray-light p-3">
                        <p class="text-sm text-muted mb-1">ระดับผู้ใช้งาน (Role)</p>
                        <span class="badge"><?php echo $_SESSION['role'] == 'admin' ? 'ผู้ดูแลระบบ' : 'นักศึกษา'; ?></span>
                    </div>
                    <div class="card bg-gray-light p-3">
                        <p class="text-sm text-muted mb-1">จัดการความปลอดภัย</p>
                        <a href="../auth/change_password.php" class="btn-outline px-3 py-2 text-sm">เปลี่ยนรหัสผ่าน</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>