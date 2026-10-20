<?php
session_start();
require_once '../../config/Database.php';

// ===== ตรวจสิทธิ์ผู้ดูแล =====
// ตรวจ session และ role ก่อนเปิดหน้าจัดการคอร์สฟรี
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// ===== เตรียมคลาสและการเชื่อมต่อ =====
require_once '../../classes/FreeCourse.php';
$database = new Database();
$db = $database->getConnection();
$courseObj = new FreeCourse($db);

// ===== รับคำขอลบคอร์ส =====
// ถ้ามี delete_id ใน URL ให้เรียก FreeCourse::delete() แล้วกลับหน้ารายการ
if (isset($_GET['delete_id'])) {
    // เมธอด delete() จัดการข้อมูลและไฟล์รูปของคอร์สที่ระบุ
    $courseObj->delete($_GET['delete_id']); 
    header("Location: manage_free_courses.php"); 
    exit();
}

// ===== อ่านรายการและเตรียมส่วนหัว =====
$stmt_courses = $courseObj->readAll();

$page_title = "จัดการคอร์สฟรี - Course Review";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>
<!-- ===== แสดงรายการคอร์สฟรี ===== -->
<div class="container py-5">
    <div class="mb-3">
        <a href="../index.php" class="text-muted no-underline">⬅️ กลับไปหน้าแดชบอร์ด</a>
    </div>
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-dark m-0">จัดการคอร์สเรียนฟรี (CourseFree)</h2>
        <a href="add_free_course.php" class="btn btn-success w-auto px-4">เพิ่มคอร์สฟรีใหม่</a>
    </div>

    <div class="card p-0">
        <div style="overflow-x: auto;">
            <table class="table mb-0">
                <thead class="bg-gray-light">
                    <tr>
                        <th style="width: 10%;">รูปปก</th>
                        <th style="width: 35%;">ชื่อคอร์ส</th>
                        <th style="width: 20%;">ผู้สอน/แพลตฟอร์ม</th>
                        <th style="width: 15%;">หมวดหมู่</th>
                        <th style="width: 20%; text-align: center;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- วนแสดงรายละเอียดคอร์ส พร้อมรูปปกและลิงก์แก้ไขหรือลบ -->
                    <?php if($stmt_courses->rowCount() > 0): while ($row = $stmt_courses->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td>
                            <?php 
                            // ถ้าไม่มีชื่อรูปปก ให้ใช้ชื่อไฟล์ภาพสำรองสำหรับการแสดงผล
                            $display_image = !empty($row['cover_image']) ? $row['cover_image'] : 'no_image.png'; 
                            $image_path = $display_image === 'no_image.png' ? '../../assets/images/' : '../../assets/uploads/';
                            ?>
                            <img src="<?php echo htmlspecialchars($image_path . $display_image, ENT_QUOTES, 'UTF-8'); ?>" alt="Cover" class="w-full rounded-md object-cover h-12" style="max-width: 80px;">
                        </td>
                        <td>
                            <div class="font-medium text-primary"><?php echo htmlspecialchars($row['title']); ?></div>
                            <?php if($row['has_certificate'] == 1): ?>
                                <span class="text-success text-xs font-bold mt-1 inline-block">มีใบเซอร์</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="font-semibold text-dark"><?php echo htmlspecialchars($row['provider']); ?></span></td>
                        <td><span class="badge"><?php echo htmlspecialchars($row['category']); ?></span></td>
                        <td class="text-center">
                            <a href="edit_free_course.php?id=<?php echo urlencode($row['course_id']); ?>" class="text-warning text-sm font-medium no-underline mr-3">✏️ แก้ไข</a>
                            <a href="manage_free_courses.php?delete_id=<?php echo urlencode($row['course_id']); ?>" class="text-danger text-sm font-medium no-underline" onclick="return confirm('ยืนยันการลบคอร์สนี้ใช่หรือไม่?');">🗑️ ลบ</a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">ยังไม่มีคอร์สฟรีในระบบ</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>