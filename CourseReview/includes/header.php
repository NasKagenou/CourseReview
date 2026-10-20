<!DOCTYPE html>
<html lang="th">
<head>
    <!-- ส่วนกำหนดข้อมูลเอกสาร: ภาษา การเข้ารหัส และการแสดงผลบนหน้าจอแต่ละขนาด -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ใช้ชื่อหน้าที่แต่ละ PHP page กำหนดไว้; ถ้าไม่ได้กำหนดให้ใช้ชื่อเว็บไซต์ -->
    <title><?php echo htmlspecialchars($page_title ?? 'Course Review', ENT_QUOTES, 'UTF-8'); ?></title>

    <!-- โหลด CSS หลักทุกหน้า; ค่าเวลาเปลี่ยน URL เพื่อให้เบราว์เซอร์ดึงไฟล์ล่าสุด -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">

    <!-- โหลดสไตล์มาสคอตเฉพาะผู้ที่มี user_id ใน session หลังเข้าสู่ระบบ -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/page-mascot.css?v=<?php echo time(); ?>">
    <?php endif; ?>
</head>
<body>