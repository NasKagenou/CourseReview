<?php
// ทุกหน้าที่มีเมนูเรียกไฟล์นี้ แล้วเลือกชุดเมนูตาม role ที่เก็บไว้ใน session
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    // Admin เห็นเมนูสำหรับจัดการระบบ
    require_once 'navbar_admin.php';
} else {
    // ผู้ใช้ทั่วไปเห็นเมนูฝั่งนักศึกษา
    require_once 'navbar_user.php';
}
?>

<!-- โหลดพฤติกรรมมาสคอตเมื่อมีผู้ใช้เข้าสู่ระบบและ navbar แสดงปุ่มมาสคอต -->
<?php if (isset($_SESSION['user_id'])): ?>
<script src="<?php echo htmlspecialchars(BASE_URL . 'assets/js/page-mascot.js?v=' . time(), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php endif; ?>