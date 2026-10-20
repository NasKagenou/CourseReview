<?php
// จัดการรายการคอร์สฟรีที่ผู้ใช้บันทึกไว้ โดยใช้การเชื่อมต่อ PDO ที่รับเข้ามา
class Bookmark {
    private $conn;
    private $table_name = "free_course_bookmarks";

    // รับการเชื่อมต่อฐานข้อมูลจากหน้าที่สร้างคลาส เพื่อใช้ในเมธอดทั้งหมด
    public function __construct($db) { 
        $this->conn = $db; 
    }

    // course_free.php ส่งรหัสผู้ใช้และคอร์สเข้ามา เพื่อเพิ่มหรือลบรายการบันทึก
    // คืน "added" เมื่อเพิ่ม และ "removed" เมื่อลบรายการเดิม
    public function toggle($user_id, $course_id) {
        // ตรวจว่าคู่นี้มีรายการอยู่หรือไม่ก่อนเลือกว่าจะลบหรือเพิ่ม
        if ($this->isBookmarked($user_id, $course_id)) {
            $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id AND course_id = :course_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->execute();
            return "removed";
        } else {
            $query = "INSERT INTO " . $this->table_name . " (user_id, course_id) VALUES (:user_id, :course_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->execute();
            return "added";
        }
    }

    // รับรหัสผู้ใช้และคอร์ส แล้วคืน true หากบันทึกคอร์สนี้ไว้ หรือ false หากยังไม่มี
    public function isBookmarked($user_id, $course_id) {
        $query = "SELECT bookmark_id FROM " . $this->table_name . " WHERE user_id = :user_id AND course_id = :course_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':course_id', $course_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // student/profile.php ส่งรหัสผู้ใช้เข้ามาเพื่ออ่านคอร์สที่บันทึกไว้ เรียงจากรายการใหม่ไปเก่า
    // คืน PDOStatement ให้หน้าเรียกนำรายการไปแสดง
    public function getMyBookmarks($user_id) {
        $query = "SELECT c.*, b.created_at as bookmarked_at 
                  FROM " . $this->table_name . " b 
                  JOIN free_courses c ON b.course_id = c.course_id 
                  WHERE b.user_id = :user_id 
                  ORDER BY b.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt;
    }
}
?>