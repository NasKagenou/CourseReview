<?php
session_start();
require_once '../../config/Database.php';

// ===== ตรวจสิทธิ์ผู้ดูแล =====
// ตรวจ session และ role ก่อนเปิดหน้าจัดการรายวิชาหลัก
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// ===== เตรียมคลาสและการเชื่อมต่อ =====
require_once '../../classes/Course.php';
$database = new Database();
$db = $database->getConnection();
$course = new Course($db);

// ===== รับคำขอลบรายวิชา =====
// ถ้ามี delete_id ใน URL ให้ Course::delete() ลบรายการ แล้วกลับหน้ารวม
if (isset($_GET['delete_id'])) {
    $course->delete($_GET['delete_id']); 
    header("Location: manage_courses.php"); 
    exit();
}

// ===== อ่านข้อมูลและเตรียมส่วนหัว =====
$stmt_courses = $course->readAll();

$page_title = "จัดการรายวิชา - Course Review";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>
<!-- ===== แสดงรายวิชาและลิงก์จัดการ ===== -->
<div class="container py-5">
    <div class="mb-3">
        <a href="../index.php" class="text-muted no-underline">⬅️ กลับไปหน้าแดชบอร์ด</a>
    </div>
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-dark m-0">จัดการรายวิชาทั้งหมด</h2>
        <a href="add_course.php" class="btn btn-success w-auto px-4">เพิ่มวิชาใหม่</a>
    </div>

    <div class="card">
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 15%;">รหัสวิชา</th>
                        <th style="width: 35%;">ชื่อวิชา</th>
                        <th style="width: 10%;">หน่วยกิต</th>
                        <th style="width: 20%;">หมวดหมู่</th>
                        <th style="width: 20%; text-align: center;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- อ่านข้อมูลทีละแถวเพื่อแสดงรายละเอียดวิชาและลิงก์แก้ไขหรือลบ -->
                    <?php while ($row = $stmt_courses->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>

                        <td><strong class="text-primary"><?php echo htmlspecialchars($row['course_id']); ?></strong></td>
                        <td>
                            <div class="font-medium text-dark"><?php echo htmlspecialchars($row['course_name']); ?></div>
                            <div class="text-xs text-muted mt-2">อ. <?php echo htmlspecialchars($row['teacher_name']); ?></div>
                        </td>
                        <td><span class="font-semibold text-muted"><?php echo htmlspecialchars($row['credits']); ?></span></td>
                        <td><span class="badge"><?php echo htmlspecialchars($row['category_name']); ?></span></td>
                        <td class="text-center">
                            <a href="edit_course.php?id=<?php echo urlencode($row['course_id']); ?>" class="text-warning text-sm font-medium no-underline mr-3">✏️ แก้ไข</a>
                            <a href="manage_courses.php?delete_id=<?php echo urlencode($row['course_id']); ?>" class="text-danger text-sm font-medium no-underline" onclick="return confirm('ยืนยันการลบวิชานี้ใช่หรือไม่?');">🗑️ ลบ</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>