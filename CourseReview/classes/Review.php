<?php
// จัดการรีวิวรายวิชา การโหวตที่เป็นประโยชน์ และข้อความตอบกลับ
class Review {
    private $conn;
    private $table_name = "reviews";
    // รับการเชื่อมต่อ PDO จาก course_detail.php หรือ admin/index.php เพื่อใช้กับข้อมูลรีวิว
    public function __construct($db) { $this->conn = $db; }
    // course_detail.php ส่งรหัสวิชาเพื่ออ่านรีวิว เรียงตามจำนวน upvote และวันที่ใหม่
    // คืน PDOStatement พร้อมชื่อผู้รีวิวและจำนวน upvote
    public function readByCourse($course_id) {
        // คำสั่งย่อยนับ upvote ของแต่ละรีวิว เพื่อนำไปเรียงผลลัพธ์
        $query = "SELECT r.review_id, r.rating, r.comment, r.created_at, u.username, 
                  (SELECT COUNT(*) FROM review_votes rv WHERE rv.review_id = r.review_id AND rv.vote_type = 'upvote') as upvotes 
                  FROM " . $this->table_name . " r 
                  LEFT JOIN users u ON r.user_id = u.user_id 
                  WHERE r.course_id = :course_id 
                  ORDER BY upvotes DESC, r.created_at DESC";
                  
        $stmt = $this->conn->prepare($query); 
        $stmt->bindParam(':course_id', $course_id); 
        $stmt->execute(); 
        return $stmt;
    }
    // course_detail.php ส่งรหัสผู้ใช้ วิชา คะแนน และข้อความจากแบบฟอร์มเพื่อสร้างรีวิว
    // คืน true เมื่อบันทึกสำเร็จ หรือ false เมื่อไม่สำเร็จ
    public function create($user_id, $course_id, $rating, $comment) {
        $query = "INSERT INTO " . $this->table_name . " (user_id, course_id, rating, comment) VALUES (:user_id, :course_id, :rating, :comment)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id); $stmt->bindParam(':course_id', $course_id);
        $stmt->bindParam(':rating', $rating); $stmt->bindParam(':comment', $comment); return $stmt->execute();
    }
    // upvote() ส่งรหัสรีวิวและผู้ใช้มาตรวจประวัติการโหวต
    // คืน true หากเคยโหวตแล้ว หรือ false หากยังไม่เคย
    public function hasVoted($review_id, $user_id) {
        $query = "SELECT vote_id FROM review_votes WHERE review_id = :review_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query); $stmt->bindParam(':review_id', $review_id); $stmt->bindParam(':user_id', $user_id); $stmt->execute(); return $stmt->rowCount() > 0;
    }
    // course_detail.php ส่งรหัสรีวิวและผู้ใช้เพื่อเพิ่ม upvote; คืน false หากเคยโหวตแล้ว
    // หากยังไม่เคยโหวต คืนผล true/false จากการเพิ่มข้อมูล
    public function upvote($review_id, $user_id) {
        // ตรวจประวัติการโหวตก่อน เพื่อไม่เพิ่มรายการซ้ำ
        if ($this->hasVoted($review_id, $user_id)) return false;
        $query = "INSERT INTO review_votes (review_id, user_id, vote_type) VALUES (:review_id, :user_id, 'upvote')";
        $stmt = $this->conn->prepare($query); $stmt->bindParam(':review_id', $review_id); $stmt->bindParam(':user_id', $user_id); return $stmt->execute();
    }
    // course_detail.php ส่งรหัสรีวิวที่ต้องการลบ และรับ true/false ตามผลคำสั่งฐานข้อมูล
    public function delete($review_id) {
    $query = "DELETE FROM " . $this->table_name . " WHERE review_id = :review_id";
    $stmt = $this->conn->prepare($query); 
    $stmt->bindParam(':review_id', $review_id); 
    return $stmt->execute();
}
    // student/profile.php ส่งรหัสผู้ใช้เพื่ออ่านรีวิวที่เขียน เรียงรายการใหม่ก่อน
    // คืน PDOStatement พร้อมข้อมูลวิชาที่เกี่ยวข้อง
    public function readByUserId($user_id) {
        $query = "SELECT r.review_id, r.rating, r.comment, r.created_at, c.course_id, c.course_name
                  FROM " . $this->table_name . " r
                  LEFT JOIN courses c ON r.course_id = c.course_id
                  WHERE r.user_id = :user_id
                  ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt;
    }

    // course_detail.php ส่งรหัสรีวิวเพื่ออ่านคำตอบพร้อมชื่อและบทบาทผู้ตอบ เรียงเก่าก่อนไปใหม่
    // คืน PDOStatement
    public function getReplies($review_id) {
        $query = "SELECT rr.reply_id, rr.reply_text, rr.created_at, u.username, u.role 
                  FROM review_replies rr 
                  LEFT JOIN users u ON rr.user_id = u.user_id 
                  WHERE rr.review_id = :review_id 
                  ORDER BY rr.created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':review_id', $review_id);
        $stmt->execute();
        return $stmt;
    }

    // course_detail.php ส่งรหัสรีวิว ผู้ตอบ และข้อความเพื่อตอบกลับรีวิว
    // คืน true เมื่อบันทึกสำเร็จ หรือ false เมื่อไม่สำเร็จ
    public function addReply($review_id, $user_id, $reply_text) {
        $query = "INSERT INTO review_replies (review_id, user_id, reply_text) VALUES (:review_id, :user_id, :reply_text)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':review_id', $review_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':reply_text', $reply_text);
        return $stmt->execute();
    }

    // admin/index.php อ่านจำนวนรีวิวทั้งหมดเพื่อแสดงบนแดชบอร์ด
    public function getTotal() {
    $stmt = $this->conn->query("SELECT COUNT(*) FROM " . $this->table_name);
    return $stmt->fetchColumn();
    }
}
?>