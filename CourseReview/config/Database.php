<?php
// กำหนดส่วนต้นของ URL ให้หน้าอื่นนำไปต่อกับพาธของหน้าได้
define('BASE_URL', '/CourseReview/');
// ตั้งเขตเวลาเริ่มต้นของ PHP ให้เป็นเวลาในประเทศไทย
date_default_timezone_set('Asia/Bangkok');

// จัดการการเชื่อมต่อฐานข้อมูล MySQL และคืนการเชื่อมต่อ PDO ให้หน้าเรียกใช้
class Database {
    private $host = "localhost";
    private $db_name = "course_review_db";
    private $username = "root";
    private $password = ""; 
    public $conn;

    // สร้างการเชื่อมต่อ PDO ด้วยค่าการตั้งค่าของคลาส แล้วคืนการเชื่อมต่อ
    // หากเชื่อมต่อไม่สำเร็จ จะแสดงข้อความข้อผิดพลาดและคืนค่า null
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
            // ตั้งเขตเวลาของการเชื่อมต่อ MySQL ให้ตรงกับเวลาในประเทศไทย
            $this->conn->exec("SET time_zone = '+07:00'"); 
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>