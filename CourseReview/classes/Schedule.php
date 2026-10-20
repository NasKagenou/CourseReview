<?php
// จัดการรายวิชาที่ผู้ใช้เพิ่มลงตารางเรียน รวมถึงวันและเวลาเรียน
class Schedule {
    private $conn;
    private $table_name = "my_schedule";

    // รับการเชื่อมต่อ PDO จากหน้าที่สร้างคลาส เพื่อใช้กับตาราง my_schedule
    public function __construct($db) {
        $this->conn = $db;
    }

    // course_detail.php ส่งรหัสผู้ใช้และวิชาเพื่อเพิ่มลงตารางเรียน
    // คืน "exists" หากมีรายการอยู่แล้ว, "success" เมื่อเพิ่มสำเร็จ หรือ "error" เมื่อเพิ่มไม่สำเร็จ
    public function addCourse($user_id, $course_id) {
        // ตรวจว่าผู้ใช้นี้เพิ่มวิชานี้ไว้แล้วหรือไม่
        $check_query = "SELECT schedule_id FROM " . $this->table_name . " WHERE user_id = :user_id AND course_id = :course_id LIMIT 1";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->bindParam(':course_id', $course_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            return "exists";
        }

        // เพิ่มรายการเมื่อยังไม่พบข้อมูลเดิม
        $query = "INSERT INTO " . $this->table_name . " (user_id, course_id) VALUES (:user_id, :course_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':course_id', $course_id);
        
        return $stmt->execute() ? "success" : "error";
    }

    // schedule.php ส่งรหัสผู้ใช้และวิชาเพื่อลบรายการของผู้ใช้นั้น
    // คืน true เมื่อคำสั่งสำเร็จ หรือ false เมื่อไม่สำเร็จ
    public function removeCourse($user_id, $course_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id AND course_id = :course_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':course_id', $course_id);
        return $stmt->execute();
    }

    // edit_schedule.php ส่งรหัสผู้ใช้ วิชา วัน เวลาเริ่ม และเวลาจบเพื่ออัปเดตตารางเรียน
    // คืน true เมื่ออัปเดตสำเร็จ หรือ false เมื่อไม่สำเร็จ
    public function updateTime($user_id, $course_id, $teach_day, $start_time, $end_time) {
        $query = "UPDATE " . $this->table_name . " 
                  SET teach_day = :teach_day, start_time = :start_time, end_time = :end_time 
                  WHERE user_id = :user_id AND course_id = :course_id";
        $stmt = $this->conn->prepare($query);
        
        // แปลงค่าที่ว่างให้เป็น NULL ก่อนส่งไปฐานข้อมูล
        $teach_day = empty($teach_day) ? null : $teach_day;
        $start_time = empty($start_time) ? null : $start_time;
        $end_time = empty($end_time) ? null : $end_time;

        $stmt->bindParam(':teach_day', $teach_day);
        $stmt->bindParam(':start_time', $start_time);
        $stmt->bindParam(':end_time', $end_time);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':course_id', $course_id);
        
        return $stmt->execute();
    }

    // schedule.php ส่งรหัสผู้ใช้เพื่ออ่านตารางเรียนพร้อมชื่อวิชา หน่วยกิต และเวลา
    // คืน PDOStatement โดยเรียงตามวันและเวลาเริ่ม
    public function getMySchedule($user_id) {
        $query = "SELECT s.course_id, c.course_name, s.teach_day, s.start_time, s.end_time, c.credits
                  FROM " . $this->table_name . " s
                  LEFT JOIN courses c ON s.course_id = c.course_id
                  WHERE s.user_id = :user_id
                  ORDER BY 
                    FIELD(s.teach_day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                    s.start_time ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt;
    }

    // course_detail.php ตรวจรายการเดิม และ edit_schedule.php อ่านรายการเพื่อเติมข้อมูลในฟอร์ม
    // คืน associative array หรือ false หากไม่พบรายการ
    public function getScheduleOne($user_id, $course_id) {
        $query = "SELECT s.course_id, c.course_name, s.teach_day, s.start_time, s.end_time 
                  FROM " . $this->table_name . " s
                  LEFT JOIN courses c ON s.course_id = c.course_id
                  WHERE s.user_id = :user_id AND s.course_id = :course_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':course_id', $course_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>