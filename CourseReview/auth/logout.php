<?php
// ===== ออกจากระบบ =====
session_start();
require_once '../config/Database.php';

// ล้างข้อมูล session ปิด session แล้ว redirect ไปหน้าเข้าสู่ระบบ
session_unset();
session_destroy();
header("Location: " . BASE_URL . "auth/login.php");
exit();
?>