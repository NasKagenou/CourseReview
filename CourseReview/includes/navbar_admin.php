<!-- เมนูสำหรับผู้ดูแลระบบ: dashboard, หน้าผู้ใช้ และออกจากระบบ -->
<nav class="navbar border-b-primary">
    <a href="<?php echo BASE_URL; ?>admin/index.php" class="nav-logo">CourseReview <span class="text-sm text-primary">(Admin)</span></a>
    <div class="nav-links flex items-center gap-3">

        <!-- มาสคอตปรากฏใน navbar เมื่อผู้ดูแลระบบเข้าสู่ระบบแล้ว -->
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

        <!-- แสดงบทบาทปัจจุบันและลิงก์หลักสำหรับงานผู้ดูแล -->
        <span class="font-medium text-muted">
            สถานะ: <strong class="text-primary uppercase">ผู้ดูแลระบบ</strong>
        </span>
        <a href="<?php echo BASE_URL; ?>admin/index.php" class="btn-outline nav-item">แดชบอร์ด</a>
        <a href="<?php echo BASE_URL; ?>index.php" class="btn-outline nav-item">ดูหน้าเว็บนักศึกษา</a>
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn-outline nav-item danger-hover">ออกจากระบบ</a>
    </div>
</nav>