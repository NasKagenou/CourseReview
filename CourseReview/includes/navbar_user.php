<!-- เมนูสำหรับผู้ใช้ฝั่งนักศึกษา: โปรไฟล์ รายวิชา คอร์สฟรี ตารางเรียน และออกจากระบบ -->
<nav class="navbar">
    <a href="<?php echo BASE_URL; ?>index.php" class="nav-logo">CourseReview.</a>
    <div class="nav-links flex items-center gap-3">

        <!-- แสดงมาสคอตเฉพาะเมื่อ session มี user_id; รูปสองชุดใช้ตามองและตอบสนองเมื่อคลิก -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <button
                id="page-mascot"
                class="page-mascot"
                type="button"
                aria-label="มาสคอต CourseReview กดเพื่อทักทาย"
                data-look="<?php echo htmlspecialchars(BASE_URL . 'assets/images/cat-directions.webp', ENT_QUOTES, 'UTF-8'); ?>"
                data-react="<?php echo htmlspecialchars(BASE_URL . 'assets/images/cat-reactions.webp', ENT_QUOTES, 'UTF-8'); ?>"
            >
                <span id="mascot-img" class="page-mascot__image" aria-hidden="true"></span>
            </button>
        <?php endif; ?>

        <!-- ลิงก์โปรไฟล์พร้อมชื่อจาก session; Guest เป็นค่าทดแทนกรณีไม่มี username -->
        <a href="<?php echo BASE_URL; ?>student/profile.php" class="btn-outline nav-item">
            สวัสดี, คุณ <strong class="text-primary uppercase"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></strong>
        </a>

        <!-- แสดงปุ่มกลับหน้าหลักเมื่อไม่ได้อยู่ที่หน้า index.php -->
        <?php
        $current_uri = $_SERVER['REQUEST_URI'];
        if (strpos($current_uri, 'index.php') === false && strpos($current_uri, '/') !== false):
        ?>
            <a href="<?php echo BASE_URL; ?>index.php" class="btn-outline nav-item">กลับหน้าหลัก</a>
        <?php endif; ?>

        <!-- ทางลัดไปหน้าคลังคอร์ส ตารางเรียน และออกจากระบบ -->
        <a href="<?php echo BASE_URL; ?>course_free.php" class="btn-outline nav-item">CourseFree</a>
        <a href="<?php echo BASE_URL; ?>student/schedule.php" class="btn-outline nav-item">ตารางเรียน</a>
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn-outline nav-item danger-hover">ออกจากระบบ</a>
    </div>
</nav>