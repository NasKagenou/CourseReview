<?php
// จัดการข้อเสนอรายวิชาจากผู้ใช้และสถานะการตรวจสอบของผู้ดูแล
class Suggestion {
    private $conn;
    private $table_name = "course_suggestions";

    // รับการเชื่อมต่อ PDO จากหน้าที่สร้างคลาส เพื่อใช้กับตาราง course_suggestions
    public function __construct($db) { 
        $this->conn = $db; 
    }

    // student/suggest_course.php ส่งผู้เสนอ รหัส/ชื่อวิชา ผู้สอน หน่วยกิต และหมวดหมู่เพื่อสร้างข้อเสนอ
    // คืน true เมื่อบันทึกสำเร็จ หรือ false เมื่อเกิดข้อผิดพลาดฐานข้อมูล
    public function create($user_id, $course_id, $course_name, $teacher_name, $credits, $category_id) {
        try {
            $query = "INSERT INTO " . $this->table_name . " (user_id, course_id, course_name, teacher_name, credits, category_id) VALUES (:user_id, :course_id, :course_name, :teacher_name, :credits, :category_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->bindParam(':course_name', $course_name);
            $stmt->bindParam(':teacher_name', $teacher_name);
            $stmt->bindParam(':credits', $credits);
            $stmt->bindParam(':category_id', $category_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // admin/courses/manage_suggestions.php อ่านข้อเสนอพร้อมชื่อผู้ส่งและชื่อหมวดหมู่
    // คืน PDOStatement โดยแสดงรายการรอตรวจสอบก่อน
    public function readAll() {
        $query = "SELECT s.*, u.username, c.category_name 
                  FROM " . $this->table_name . " s 
                  LEFT JOIN users u ON s.user_id = u.user_id 
                  LEFT JOIN categories c ON s.category_id = c.category_id
                  ORDER BY FIELD(s.status, 'pending', 'approved', 'rejected'), s.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // รับรหัสข้อเสนอเพื่ออ่านข้อมูลหนึ่งรายการ และคืน associative array หรือ false หากไม่พบ
    public function getOne($suggestion_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE suggestion_id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $suggestion_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // admin/courses/manage_suggestions.php ส่งรหัสข้อเสนอและสถานะใหม่เพื่ออัปเดตผลตรวจ
    // คืน true เมื่ออัปเดตสำเร็จ หรือ false เมื่อไม่สำเร็จ
    public function updateStatus($suggestion_id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE suggestion_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $suggestion_id);
        return $stmt->execute();
    }
}
?>