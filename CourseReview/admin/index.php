<?php
session_start();
require_once '../config/Database.php';

// ===== ตรวจสิทธิ์ผู้ดูแล =====
// ใช้ข้อมูลจาก session ตรวจว่ามีผู้ใช้และมี role เป็น admin ก่อนแสดงแดชบอร์ด
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// ===== เตรียมการเชื่อมต่อและคลาสที่ใช้ =====
$database = new Database();
$db = $database->getConnection();

require_once '../classes/Course.php';
require_once '../classes/Review.php';
require_once '../classes/FreeCourse.php';
require_once '../classes/User.php';

$courseObj = new Course($db);
$reviewObj = new Review($db);
$freeCourseObj = new FreeCourse($db);
$userObj = new User($db);

$total_courses = $courseObj->getTotal();
$total_reviews = $reviewObj->getTotal();
$total_free_courses = $freeCourseObj->getTotal();
$total_users = $userObj->getTotal();

// ===== อ่านจำนวนวิชาในแต่ละหมวดหมู่ =====
// คำสั่งฐานข้อมูลนี้เตรียมชื่อหมวดและจำนวนวิชาไว้ส่งให้กราฟด้านล่าง
$stmt_cat_stats = $db->query("
    SELECT cat.category_name, COUNT(c.course_id) as course_count 
    FROM categories cat 
    LEFT JOIN courses c ON cat.category_id = c.category_id 
    GROUP BY cat.category_id
");
$cat_labels = [];
$cat_data = [];
while ($row = $stmt_cat_stats->fetch(PDO::FETCH_ASSOC)) {
    $cat_labels[] = $row['category_name'];
    $cat_data[] = $row['course_count'];
}

// ===== เตรียมส่วนหัวของหน้า =====
$page_title = "แดชบอร์ดผู้ดูแลระบบ";
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<!-- ===== โหลดเครื่องมือสำหรับวาดกราฟ =====
     Chart.js ใช้วาดกราฟจากข้อมูลที่เตรียมไว้ในส่วนสคริปต์ด้านล่าง -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- ===== แสดงภาพรวมและเมนูจัดการ ===== -->
<div class="container py-5">
    <h2 class="mb-4 text-dark">ภาพรวมระบบ (Admin Dashboard)</h2>
    
    <!-- แต่ละการ์ดแสดงผลจำนวนที่ได้จากเมธอด getTotal() ของคลาสที่เกี่ยวข้อง -->
    <div class="dashboard-grid mb-5" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="card text-center bg-gradient-primary">
            <h4 class="text-white mb-2 font-medium">ผู้ใช้งาน</h4>
            <div class="text-white text-4xl font-bold"><?php echo $total_users; ?></div>
        </div>
        <div class="card text-center bg-gradient-success">
            <h4 class="text-white mb-2 font-medium">รายวิชาหลัก</h4>
            <div class="text-white text-4xl font-bold"><?php echo $total_courses; ?></div>
        </div>
        <div class="card text-center bg-gradient-info">
            <h4 class="text-white mb-2 font-medium">คอร์สฟรี</h4>
            <div class="text-white text-4xl font-bold"><?php echo $total_free_courses; ?></div>
        </div>
        <div class="card text-center bg-warning-light border-0">
            <h4 class="text-warning mb-2 font-medium">รีวิวทั้งหมด</h4>
            <div class="text-warning text-4xl font-bold"><?php echo $total_reviews; ?></div>
        </div>
    </div>

    <!-- พื้นที่สำหรับแสดงกราฟสรุปข้อมูลและกราฟจำนวนวิชาแยกตามหมวดหมู่ -->
    <div class="form-grid-2 mb-5">
        <div class="card">
            <h4 class="text-dark mb-4 text-center">สัดส่วนข้อมูลในระบบ</h4>
            <canvas id="overviewChart" height="200"></canvas>
        </div>
        <div class="card">
            <h4 class="text-dark mb-4 text-center">จำนวนรายวิชาแยกตามหมวดหมู่</h4>
            <canvas id="categoryChart" height="200"></canvas>
        </div>
    </div>

    <!-- ลิงก์เหล่านี้พาไปยังหน้าจัดการข้อมูลแต่ละส่วนของระบบ -->
    <div class="card p-0 overflow-hidden">
        <div class="bg-gray-light p-3 border-b border-gray-300">
            <h3 class="text-dark m-0">เมนูการจัดการ</h3>
        </div>
        <div class="flex flex-col">
            <div class="flex justify-between items-center p-4 border-b border-gray-200 hover:bg-gray-50 transition">
                <div>
                    <h4 class="text-primary m-0">จัดการวิชามหาลัย</h4>
                    <p class="text-sm text-muted m-0 mt-1">เพิ่ม แก้ไข หรือลบรายวิชาในระบบหลักสูตร</p>
                </div>
                <a href="courses/manage_courses.php" class="btn w-auto px-4">เข้าสู่ระบบจัดการ</a>
            </div>
            
            <div class="flex justify-between items-center p-4 border-b border-gray-200 hover:bg-gray-50 transition">
                <div>
                    <h4 class="text-success m-0">จัดการคอร์สเรียนฟรี</h4>
                    <p class="text-sm text-muted m-0 mt-1">ดูแลข้อมูลคลังคอร์สเรียนฟรี (CourseFree)</p>
                </div>
                <a href="free_courses/manage_free_courses.php" class="btn-success w-auto px-4 py-2 rounded-full text-white font-semibold no-underline text-sm">เข้าสู่ระบบจัดการ</a>
            </div>

            <div class="flex justify-between items-center p-4 border-b border-gray-200 hover:bg-gray-50 transition">
                <div>
                    <h4 class="text-warning m-0">จัดการ Feedback</h4>
                    <p class="text-sm text-muted m-0 mt-1">ตรวจสอบปัญหาการใช้งานและข้อเสนอแนะจากนักศึกษา</p>
                </div>
                <a href="system/manage_feedbacks.php" class="btn-outline w-auto px-4">ดู Feedback</a>
            </div>

            <div class="flex justify-between items-center p-4 border-b border-gray-200 hover:bg-gray-50 transition">
                <div>
                    <h4 class="text-info m-0" style="color: #0ea5e9;">ตรวจสอบข้อเสนอรายวิชา</h4>
                    <p class="text-sm text-muted m-0 mt-1">อนุมัติรายวิชาใหม่ที่นักศึกษาเสนอเข้ามายังระบบ</p>
                </div>
                <a href="courses/manage_suggestions.php" class="btn-outline w-auto px-4">ดูคำขอเสนอวิชา</a>
            </div>

            <div class="flex justify-between items-center p-4 hover:bg-gray-50 transition">
                <div>
                    <h4 class="text-dark m-0">ประวัติกิจกรรม (Logs)</h4>
                    <p class="text-sm text-muted m-0 mt-1">ตรวจสอบประวัติการทำงานและการอัปเดตต่างๆ</p>
                </div>
                <a href="system/activity_logs.php" class="btn-outline w-auto px-4">ตรวจสอบประวัติ</a>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>

<script>
// ===== วาดกราฟเปรียบเทียบจำนวนข้อมูล =====
// ใช้ยอดรวมที่ PHP อ่านไว้ เพื่อแสดงจำนวนผู้ใช้ วิชา คอร์สฟรี และรีวิว
const ctxOverview = document.getElementById('overviewChart').getContext('2d');
new Chart(ctxOverview, {
    type: 'bar',
    data: {
        labels: ['ผู้ใช้งาน', 'รายวิชาหลัก', 'คอร์สฟรี', 'รีวิว'],
        datasets: [{
            label: 'จำนวน',
            data: [<?php echo "$total_users, $total_courses, $total_free_courses, $total_reviews"; ?>],
            backgroundColor: [
                'rgba(99, 102, 241, 0.7)',
                'rgba(16, 185, 129, 0.7)',
                'rgba(6, 182, 212, 0.7)',
                'rgba(245, 158, 11, 0.7)'
            ],
            borderRadius: 6
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

// ===== วาดกราฟจำนวนวิชาแยกตามหมวดหมู่ =====
// ใช้ชื่อหมวดและจำนวนวิชาที่ PHP เตรียมไว้เป็นป้ายกำกับและค่าของกราฟ
const ctxCategory = document.getElementById('categoryChart').getContext('2d');
new Chart(ctxCategory, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($cat_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($cat_data); ?>,
            backgroundColor: [
                'rgba(236, 72, 153, 0.7)',
                'rgba(139, 92, 246, 0.7)',
                'rgba(59, 130, 246, 0.7)',
                'rgba(245, 158, 11, 0.7)'
            ],
            borderWidth: 0
        }]
    },
    options: { responsive: true, cutout: '70%' }
});
</script>

<?php require_once '../includes/footer.php'; ?>