<?php
// ===== เริ่มต้น session และเตรียมคลาส =====
session_start();
require_once 'config/Database.php';
require_once 'classes/FreeCourse.php';
require_once 'classes/Bookmark.php';

$database = new Database();
$db = $database->getConnection();
$courseObj = new FreeCourse($db);
$bookmarkObj = new Bookmark($db);

// อ่านตัวกรองจาก URL เพื่อนำไปค้นหาและแสดงค่าเดิมในแบบฟอร์ม
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : "";
$filter_category = isset($_GET['category']) ? $_GET['category'] : "";
$filter_language = isset($_GET['language']) ? $_GET['language'] : "";
$filter_cert = isset($_GET['has_cert']) ? $_GET['has_cert'] : "";

// ===== บันทึกหรือยกเลิกการบันทึกคอร์ส =====
// ปุ่มหัวใจส่ง course_id; ต้องล็อกอินก่อนเรียก Bookmark::toggle() สลับสถานะรายการ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_bookmark'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: auth/login.php");
        exit();
    }
    $course_id_to_toggle = $_POST['course_id'];
    $bookmarkObj->toggle($_SESSION['user_id'], $course_id_to_toggle);
    
    // redirect กลับหน้าคลังโดยคงตัวกรองเดิมใน query string
    $query_string = $_SERVER['QUERY_STRING'];
    header("Location: course_free.php?" . $query_string);
    exit();
}

$stmt = $courseObj->readAll($search_keyword, $filter_category, $filter_language, $filter_cert);

// ===== แสดงตัวกรองและผลลัพธ์คอร์ส =====
$page_title = "คลังคอร์สเรียนฟรี (CourseFree) - Course Review";
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>
<div class="container grid-layout py-5">
    
    <aside>
        <div class="card">
            <h3 class="mb-4 text-center">🔍 ค้นหาและตัวกรอง</h3>
            <form action="course_free.php" method="GET" class="m-0">
                <div class="input-group">
                    <label>คำค้นหา (Keyword)</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_keyword); ?>" placeholder="ชื่อคอร์ส, แพลตฟอร์ม...">
                </div>
                <div class="input-group">
                    <label>หมวดหมู่</label>
                    <select name="category">
                        <option value="">- ทุกหมวดหมู่ -</option>
                        <option value="IT & Programming" <?php if ($filter_category == 'IT & Programming') echo 'selected'; ?>>IT & Programming</option>
                        <option value="Design & Creative" <?php if ($filter_category == 'Design & Creative') echo 'selected'; ?>>Design & Creative</option>
                        <option value="Data & Analytics" <?php if ($filter_category == 'Data & Analytics') echo 'selected'; ?>>Data & Analytics</option>
                        <option value="Language" <?php if ($filter_category == 'Language') echo 'selected'; ?>>ภาษา</option>
                        <option value="Business" <?php if ($filter_category == 'Business') echo 'selected'; ?>>ธุรกิจ</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>ภาษาที่สอน</label>
                    <select name="language">
                        <option value="">- ทุกภาษา -</option>
                        <option value="ไทย" <?php if ($filter_language == 'ไทย') echo 'selected'; ?>>🇹🇭 ภาษาไทย</option>
                        <option value="English" <?php if ($filter_language == 'English') echo 'selected'; ?>>🇬🇧 English</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>ใบประกาศนียบัตร</label>
                    <select name="has_cert">
                        <option value="">- ทั้งหมด -</option>
                        <option value="1" <?php if ($filter_cert === '1') echo 'selected'; ?>>มี Certificate ฟรี</option>
                        <option value="0" <?php if ($filter_cert === '0') echo 'selected'; ?>>ไม่มี Certificate</option>
                    </select>
                </div>
                <div class="mt-4 flex flex-col gap-2">
                    <button type="submit" class="btn">กรองข้อมูล</button>
                    <?php if ($search_keyword != "" || $filter_category != "" || $filter_language != "" || $filter_cert != ""): ?>
                        <a href="course_free.php" class="btn-outline text-center">ล้างตัวกรอง</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </aside>
    
    <main>
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-dark m-0">คลังคอร์สเรียนฟรี (CourseFree)</h2>
        </div>

        <div class="course-grid">
            <?php if ($stmt->rowCount() > 0): while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <?php 
                $is_bookmarked = false;
                if (isset($_SESSION['user_id'])) {
                    $is_bookmarked = $bookmarkObj->isBookmarked($_SESSION['user_id'], $row['course_id']);
                }
                ?>
                <div class="card flex flex-col p-0">
                    
                    <!-- แสดงรูปปกของคอร์ส หรือรูปสำรองเมื่อไม่มีไฟล์รูป -->
                    <div class="course-cover-wrapper bg-gray-light border-b">
                        <?php 
                        // ใช้ no_image.png เป็นค่าเริ่มต้นหากไม่มีชื่อไฟล์รูปในข้อมูล
                        $display_image = !empty($row['cover_image']) ? $row['cover_image'] : 'no_image.png'; 
                        $image_path = $display_image === 'no_image.png' ? 'assets/images/' : 'assets/uploads/';
                        ?>
                        <img src="<?php echo htmlspecialchars($image_path . $display_image, ENT_QUOTES, 'UTF-8'); ?>" alt="Cover" class="w-full course-cover-img" style="aspect-ratio: 16/9; object-fit: cover;">
                    </div>
                    
                    <!-- การ์ดแสดงข้อมูลคอร์ส ลิงก์ไปแหล่งเรียน และปุ่มบันทึก -->
                    <div class="p-4 flex flex-col flex-grow">
                        <div class="flex justify-between items-center mb-2">
                            <span class="badge m-0 text-xs"><?php echo htmlspecialchars($row['category']); ?></span>
                            <?php if ($row['has_certificate'] == 1): ?>
                                <span class="text-success text-xs font-bold">มีใบเซอร์</span>
                            <?php endif; ?>
                        </div>
                        
                        <h4 class="mt-2 mb-2 text-lg text-dark leading-relaxed flex-grow">
                            <?php echo htmlspecialchars($row['title']); ?>
                        </h4>
                        
                        <p class="text-sm text-muted mb-4 leading-loose">
                            ผู้สอน: <strong><?php echo htmlspecialchars($row['provider']); ?></strong><br>
                            ระยะเวลา: <?php echo htmlspecialchars($row['duration']); ?><br>
                            ภาษา: <?php echo htmlspecialchars($row['language']); ?><br>
                            ระดับ: <strong><?php echo htmlspecialchars($row['level']); ?></strong>
                        </p>
                        
                        <div class="flex gap-2 mt-auto">
                            <!-- เปิดแหล่งเรียนในแท็บใหม่โดยไม่ออกจากหน้าคลัง -->
                            <a href="<?php echo htmlspecialchars($row['course_url']); ?>" target="_blank" class="btn text-center flex-grow">🔗 ไปเรียน</a>
                            
                            <!-- ส่ง course_id ให้ส่วนประมวลผลบันทึก และคงตัวกรองไว้ใน action -->
                            <form action="course_free.php?<?php echo $_SERVER['QUERY_STRING']; ?>" method="POST" class="m-0">
                                <input type="hidden" name="course_id" value="<?php echo $row['course_id']; ?>">
                                <button type="submit" name="toggle_bookmark" class="btn-outline px-3" title="บันทึกคอร์สนี้">
                                    <?php echo $is_bookmarked ? '❤️' : '🩶'; ?>
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            <?php endwhile; else: ?>
                <div class="card text-center p-5 w-full">
                    <p class="text-muted text-lg m-0">ไม่พบคอร์สฟรีที่ตรงกับตัวกรองของคุณ</p>
                    <a href="course_free.php" class="text-primary font-medium mt-2 inline-block">ดูคอร์สทั้งหมด</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require_once 'includes/footer.php'; ?>