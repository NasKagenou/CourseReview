<?php
// บันทึกเหตุการณ์การใช้งานและช่วยแปลงปลายทางของบันทึกให้เป็นลิงก์ในเว็บไซต์
class Log {
    private $conn;
    private $table_name = "activity_logs";

    // รับการเชื่อมต่อ PDO จากหน้าที่สร้างคลาส เพื่อใช้กับตาราง activity_logs
    public function __construct($db) { 
        $this->conn = $db; 
    }

    // รับรหัสผู้ใช้ การกระทำ รายละเอียด และ URL ปลายทางเพื่อเพิ่มบันทึกเหตุการณ์
    // คืน true เมื่อเพิ่มสำเร็จ หรือ false เมื่อไม่สำเร็จ
    public function addLog($user_id, $action, $details, $target_url) {
        $query = "INSERT INTO " . $this->table_name . " (user_id, action, details, target_url) VALUES (:user_id, :action, :details, :target_url)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':details', $details);
        $stmt->bindParam(':target_url', $target_url);
        return $stmt->execute();
    }

    // admin/system/activity_logs.php อ่าน Log พร้อมชื่อ/บทบาท และหา review_id สำหรับลิงก์รีวิวกับคำตอบ
    // จับคู่จากผู้ใช้ วิชา และเวลาที่ทำรายการใกล้กัน แล้วคืน PDOStatement ให้หน้าแสดงผล
    public function getAllLogs() {
        $query = "SELECT l.*, u.username, u.role,
                  CASE
                    WHEN l.action = 'add_review' THEN (
                        SELECT r.review_id
                        FROM reviews r
                        WHERE r.user_id = l.user_id
                            AND r.course_id = SUBSTRING_INDEX(SUBSTRING_INDEX(l.target_url, 'id=', -1), '#', 1)
                        ORDER BY ABS(TIMESTAMPDIFF(SECOND, r.created_at, l.created_at)) ASC
                        LIMIT 1
                    )
                    WHEN l.action = 'add_reply' THEN (
                        SELECT rr.review_id
                        FROM review_replies rr
                        JOIN reviews r ON r.review_id = rr.review_id
                        WHERE rr.user_id = l.user_id
                          AND r.course_id = SUBSTRING_INDEX(SUBSTRING_INDEX(l.target_url, 'id=', -1), '#', 1)
                        ORDER BY ABS(TIMESTAMPDIFF(SECOND, rr.created_at, l.created_at)) ASC
                        LIMIT 1
                    )
                    ELSE NULL
                  END AS review_id
                  FROM " . $this->table_name . " l
                  LEFT JOIN users u ON l.user_id = u.user_id
                  ORDER BY l.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // รับชนิดกิจกรรม URL ปลายทาง และรหัสรีวิว (ถ้ามี) เพื่อสร้างลิงก์ไปยังหน้าที่เกี่ยวข้อง
    // กิจกรรมรีวิว/ตอบกลับจะมีจุดยึดให้เปิดตรงการ์ดรีวิว ส่วนกิจกรรมอื่นใช้หน้าปลายทางตามประเภท
    public function getTargetLink($action, $target_url, $review_id = null) {
        // เหตุการณ์เกี่ยวกับข้อเสนอวิชาจะลิงก์ไปหน้าจัดการข้อเสนอ
        if ($action == 'suggest_course' || $action == 'reject_suggestion') {
            return BASE_URL . 'admin/courses/manage_suggestions.php';
        }
        
        // เหตุการณ์เกี่ยวกับ Feedback จะลิงก์ไปหน้าจัดการ Feedback
        if ($action == 'add_feedback' || $action == 'resolve_feedback') {
            return BASE_URL . 'admin/system/manage_feedbacks.php';
        }

        // สำหรับเหตุการณ์เพิ่มคอร์สฟรี ใช้ query string เดิมต่อท้ายหน้าจอแก้ไขคอร์ส
        if ($action == 'add_free_course') {
            $parts = explode('?', $target_url);
            if (isset($parts[1])) {
                return BASE_URL . 'admin/free_courses/edit_free_course.php?' . $parts[1];
            }
        }

        // กิจกรรมรีวิวและคำตอบพาไปยังการ์ดรีวิวที่เกี่ยวข้องโดยตรง
        if ($action == 'add_review' || $action == 'add_reply') {
            $target_url = preg_replace('/#.*$/', '', $target_url);
            $review_anchor = $review_id ? 'review-' . (int)$review_id : 'reviews';
            return BASE_URL . $target_url . '#' . $review_anchor;
        }

        // เหตุการณ์อื่นใช้ URL ที่บันทึกไว้และเติม BASE_URL
        return BASE_URL . $target_url;
    }
}
?>