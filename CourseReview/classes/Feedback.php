<?php
// จัดการข้อความแจ้งปัญหา/ข้อเสนอแนะที่ผู้ใช้ส่งเข้ามาและสถานะการดำเนินการ
class Feedback {
    private $conn;
    private $table_name = "feedbacks";

    // รับการเชื่อมต่อ PDO จากหน้าที่สร้างคลาส เพื่อใช้เข้าถึงตาราง feedbacks
    public function __construct($db) { 
        $this->conn = $db; 
    }

    // student/feedback.php ส่งรหัสผู้ใช้ หัวข้อ และข้อความเพื่อสร้างรายการใหม่
    // คืน true เมื่อบันทึกสำเร็จ หรือ false เมื่อเกิดข้อผิดพลาดฐานข้อมูล
    public function create($user_id, $subject, $message) {
        try {
            $query = "INSERT INTO " . $this->table_name . " (user_id, subject, message) VALUES (:user_id, :subject, :message)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Feedback Create Error: " . $e->getMessage());
            return false;
        }
    }

    // อ่านรายการทั้งหมดพร้อมชื่อผู้ส่ง โดยแสดงรายการรอดำเนินการก่อน และคืน PDOStatement
    public function readAll() {
        $query = "SELECT f.*, u.username 
                  FROM " . $this->table_name . " f 
                  LEFT JOIN users u ON f.user_id = u.user_id 
                  ORDER BY FIELD(f.status, 'pending', 'resolved'), f.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // manage_feedbacks.php ส่งรหัสรายการและสถานะใหม่เพื่ออัปเดตสถานะ
    // คืน true เมื่ออัปเดตสำเร็จ หรือ false เมื่อเกิดข้อผิดพลาดฐานข้อมูล
    public function updateStatus($feedback_id, $status) {
        try {
            $query = "UPDATE " . $this->table_name . " SET status = :status WHERE feedback_id = :feedback_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':feedback_id', $feedback_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>