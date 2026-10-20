<?php
// ===== เริ่มต้น session และตรวจสิทธิ์ =====
session_start();
require_once 'config/Database.php';

// หน้าหลักนี้ให้สมาชิกใช้งาน; หากยังไม่มี user_id ให้ไป auth/login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// ===== รับตัวกรองและโหลดข้อมูลรายวิชา =====
require_once 'classes/Course.php';
$database = new Database();
$db = $database->getConnection();
$course = new Course($db);

// อ่านเงื่อนไขจาก GET เพื่อใช้ค้นหา และคงค่าเดิมไว้ในฟอร์มกับลิงก์แบ่งหน้า
$stmt_categories = $course->getCategories();
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : "";
$filter_category = isset($_GET['category']) ? $_GET['category'] : "";
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : "";

// คำนวณจำนวนหน้าและตำแหน่งเริ่มต้น; แสดงผลครั้งละ 6 วิชา
$items_per_page = 6;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

$total_items = $course->countSearchAdvanced($search_keyword, $filter_category);
$total_pages = ceil($total_items / $items_per_page);
$offset = ($current_page - 1) * $items_per_page;

// Course::searchAdvancedPaginated() โหลดเฉพาะรายการหน้าปัจจุบันตามตัวกรอง
$stmt = $course->searchAdvancedPaginated($search_keyword, $filter_category, $sort_by, $items_per_page, $offset);
// โหลดรายการยอดนิยมแยกจากผลค้นหาด้วย Course::getTopRated()
$stmt_top = $course->getTopRated();

// ===== แสดงส่วน Top 5 และลิงก์ไปหน้าที่เกี่ยวข้อง =====
$page_title = "หน้าหลัก - Course Review";
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<!-- โครงหน้าจัด Top 5 ไว้ด้านข้าง และพื้นที่หลักไว้ค้นหาและแสดงรายวิชา -->
<div class="container grid-layout py-5">

    <aside>
        <div class="card">
            <h3 class="text-center mb-4">Top 5 วิชายอดฮิต</h3>
            <ul class="list-none">
                <?php
                // กำหนดลำดับแสดงผลให้รายการที่ Course::getTopRated() ส่งกลับมา
                $rank = 1;
                if ($stmt_top->rowCount() > 0):
                    while ($top_row = $stmt_top->fetch(PDO::FETCH_ASSOC)):
                ?>
                        <li class="mb-3 pb-3 border-b">
                            <strong class="text-primary text-lg"><?php echo $rank . ". " . htmlspecialchars($top_row['course_id']); ?></strong><br>
                            <span class="text-sm text-muted"><?php echo htmlspecialchars($top_row['course_name']); ?></span><br>
                            <!-- แสดงคะแนนเฉลี่ยพร้อมจำนวนรีวิวเพื่อให้เห็นบริบทของคะแนน -->
                            <div class="mt-2 flex justify-between items-center">
                                <div>
                                    <span class="stars">★</span> <span class="font-semibold text-dark"><?php echo number_format($top_row['avg_rating'], 1); ?> / 5.0</span>
                                </div>
                                <div class="text-xs text-muted font-medium">
                                    <?php echo $top_row['total_reviews']; ?> รีวิว
                                </div>
                            </div>
                        </li>
                    <?php $rank++;
                    endwhile;
                else: ?>
                    <li class="text-sm text-muted text-center">ยังไม่มีข้อมูลคะแนน</li>
                <?php endif; ?>
            </ul>
        </div>
        <br>
        <!-- ลิงก์ทางลัดไปแบบฟอร์มเสนอรายวิชาและแจ้งข้อเสนอแนะ -->
        <div class="card text-center bg-gray-light mt-5">
            <h4 class="text-dark mb-2">🤝 มีส่วนร่วมกับเรา</h4>
            <p class="text-xs text-muted mb-4">ช่วยเราพัฒนา CourseReview ให้ดีขึ้น</p>
            
            <div class="flex flex-col gap-3">
                <a href="student/suggest_course.php" class="btn w-full text-sm">เสนอรายวิชาใหม่</a>
                <a href="student/feedback.php" class="btn-outline w-full text-sm">แจ้งปัญหา / ข้อเสนอแนะ</a>
            </div>
        </div>
    </aside>

    <main>
        <div class="card mb-5 text-center bg-gray-light">
            <h1 class="text-3xl font-bold text-dark mb-2">ยินดีต้อนรับสู่ <span class="text-primary">CourseReview</span></h1>
            <p class="text-muted text-lg leading-relaxed">
                ศูนย์รวมรีวิววิชาเรียนจากรุ่นพี่สู่รุ่นน้อง! ค้นหาวิชาที่ใช่ เตรียมตัวให้พร้อมก่อนลงทะเบียน<br>
                <span class="text-sm font-medium">ร่วมสร้างคอมมูนิตี้แบ่งปันความรู้ไปด้วยกัน</span>
            </p>
        </div>
        <!-- ส่งตัวกรองด้วย GET เพื่อให้ใช้ซ้ำจาก URL และคงค่าในช่องกรอกได้ -->
        <div class="mb-5">
            <form action="index.php" method="GET" class="m-0">
                <div class="flex flex-wrap gap-3 items-center">

                    <div class="input-group m-0 flex-grow">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_keyword); ?>" placeholder="🔍 ค้นหารหัส หรือ ชื่อวิชา..." class="m-0 w-full">
                    </div>

                    <div class="input-group m-0 flex-1">
                        <select name="category" class="m-0 w-full">
                            <option value="">ทุกหมวดหมู่</option>
                            <?php
                            $stmt_categories->execute();
                            while ($cat = $stmt_categories->fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo ($filter_category == $cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="input-group m-0 flex-1">
                        <select name="sort" class="m-0 w-full">
                            <option value="">จัดเรียง</option>
                            <option value="most_reviews" <?php echo ($sort_by == 'most_reviews') ? 'selected' : ''; ?>>รีวิวมากสุด</option>
                            <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>รีวิวล่าสุด</option>
                            <option value="H_credits" <?php echo ($sort_by == 'H_credits') ? 'selected' : ''; ?>>หน่วยกิตมากสุด</option>
                            <option value="L_credits" <?php echo ($sort_by == 'L_credits') ? 'selected' : ''; ?>>หน่วยกิตน้อยสุด</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="btn m-0 w-auto px-4">ค้นหา</button>
                        <?php if ($search_keyword != "" || $filter_category != "" || $sort_by != ""): ?>
                            <a href="index.php" class="btn-outline flex items-center justify-center m-0 px-4">ล้าง</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <h3 class="mb-4">รายวิชาที่เปิดสอน <?php if ($search_keyword != "" || $filter_category != "") echo "<span class='text-primary'>(ผลการกรอง)</span>"; ?></h3>
            
        <!-- วนแสดงรายการผลค้นหาจาก Course::searchAdvancedPaginated() หรือแจ้งเมื่อไม่พบผล -->
        <div class="course-grid">
            <?php if ($stmt->rowCount() > 0): while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?> 
                    <div class="card flex flex-col">

                        <div class="flex justify-between items-start mb-2">
                            <!-- แสดงหมวดหมู่และป้ายยอดนิยมตามข้อมูลของแต่ละผลค้นหา -->
                            <div class="flex flex-col items-start">
                                <span class="badge m-0"><?php echo htmlspecialchars($row['category_name']); ?></span>

                                <?php if ($row['review_count'] >= 6): ?>
                                    <span class="badge bg-warning-light text-warning m-0 mt-2 text-xs">🔥 ยอดฮิต</span>
                                <?php endif; ?>
                            </div>

                            <!-- คะแนนเฉลี่ยมาจากผลค้นหาที่อ่านได้จากฐานข้อมูล -->
                            <div class="flex items-center gap-2 text-primary font-bold text-lg ml-2">
                                ⭐ <span><?php echo ($row['avg_rating'] > 0) ? $row['avg_rating'] : '-'; ?></span>
                            </div>
                        </div>
                        <h4 class="mt-3 mb-2 text-lg text-dark leading-relaxed">
                            <span class="text-primary"><?php echo htmlspecialchars($row['course_id']); ?></span><br>
                            <?php echo htmlspecialchars($row['course_name']); ?>
                            
                        </h4>
                        <p class="text-sm text-muted mb-4 flex-grow leading-loose">
                            อ.ผู้สอน: <?php echo htmlspecialchars($row['teacher_name']); ?> <br>
                            หน่วยกิต: <strong><?php echo htmlspecialchars($row['credits']); ?></strong>
                        </p>
                        <a href="course_detail.php?id=<?php echo urlencode($row['course_id']); ?>" class="btn-outline w-full text-center">อ่านรีวิวและรายละเอียด</a>
                    </div>
                <?php endwhile;
            else: ?>
                <div class="card text-center p-5 w-full" style="grid-column: 1 / -1;">
                    <p class="text-muted text-lg m-0 mb-3">❌ ไม่พบรายวิชาที่ตรงกับตัวกรองของคุณ</p>
                    <div class="flex justify-center gap-3">
                        <a href="index.php" class="btn-outline w-auto px-4">ดูรายวิชาทั้งหมด</a>
                        <!-- หากไม่พบวิชา ผู้ใช้ไป student/suggest_course.php เพื่อเสนอเพิ่มได้ -->
                        <a href="student/suggest_course.php" class="btn w-auto px-4" style="background: linear-gradient(135deg, #0ea5e9, #3b82f6);">💡 เสนอรายวิชานี้เข้าระบบ</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

<!-- ลิงก์แบ่งหน้าเก็บคำค้น หมวดหมู่ และการเรียงเดิมไว้ใน URL -->
<?php if ($total_pages > 1): ?>
    <div class="pagination flex justify-center items-center gap-2">
        <?php if ($current_page > 1): ?>
            <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($filter_category); ?>&sort=<?php echo urlencode($sort_by); ?>" class="btn-outline px-3 py-1 text-sm w-auto">◀ ก่อนหน้า</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($filter_category); ?>&sort=<?php echo urlencode($sort_by); ?>"
                class="<?php echo $i == $current_page ? 'btn' : 'btn-outline'; ?> px-3 py-1 text-sm" style="width: auto;">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($filter_category); ?>&sort=<?php echo urlencode($sort_by); ?>" class="btn-outline px-3 py-1 text-sm w-auto">ถัดไป ▶</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>