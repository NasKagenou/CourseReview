<?php
// จัดการข้อมูลรายวิชาและการค้นหารายวิชา โดยรับการเชื่อมต่อ PDO จากหน้าที่เรียกใช้
class Course {
    private $conn;
    private $table_name = "courses";
    // รับการเชื่อมต่อฐานข้อมูลจากหน้าที่สร้างคลาส เพื่อใช้ในเมธอดทั้งหมด
    public function __construct($db) { $this->conn = $db; }
    
    // admin/courses/manage_courses.php อ่านทุกวิชาพร้อมชื่อหมวดหมู่ เรียงตามรหัส
    // คืน PDOStatement ให้หน้าเรียกนำรายการไปแสดง
    public function readAll() {
        $query = "SELECT c.course_id, c.course_name, c.teacher_name, c.credits, cat.category_name
        FROM " . $this->table_name . " c
        LEFT JOIN categories cat ON c.category_id = cat.category_id 
        ORDER BY c.course_id ASC";
        $stmt = $this->conn->prepare($query); $stmt->execute(); return $stmt;
    }
    
    // admin/courses/add_course.php ส่งรหัส ชื่อวิชา ผู้สอน หน่วยกิต และหมวดหมู่เพื่อเพิ่มวิชา
    // admin/courses/manage_suggestions.php ก็เรียกเมธอดนี้เมื่ออนุมัติข้อเสนอ
    // คืน true เมื่อบันทึกสำเร็จ หรือ false เมื่อคำสั่งไม่สำเร็จ
    public function create($course_id, $course_name, $teacher_name, $credits, $category_id) {
        $query = "INSERT INTO " . $this->table_name . " (course_id, course_name, teacher_name, credits, category_id) VALUES (:course_id, :course_name, :teacher_name, :credits, :category_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':course_id', $course_id); 
        $stmt->bindParam(':course_name', $course_name);
        $stmt->bindParam(':teacher_name', $teacher_name); 
        $stmt->bindParam(':credits', $credits); 
        $stmt->bindParam(':category_id', $category_id);
        return $stmt->execute();
    }
    
    // admin/courses/manage_courses.php ส่งรหัสวิชาที่ต้องการลบ
    // คืน true เมื่อคำสั่งลบสำเร็จ หรือ false เมื่อไม่สำเร็จ
    public function delete($course_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE course_id = :course_id";
        $stmt = $this->conn->prepare($query); $stmt->bindParam(':course_id', $course_id); return $stmt->execute();
    }
    
    // index.php เรียกเมธอดนี้เพื่ออ่าน 5 วิชาที่มีคะแนนถ่วงน้ำหนักสูงสุด
    // คืน PDOStatement ซึ่งมีคะแนนเฉลี่ยจริง จำนวนรีวิว และคะแนนที่ใช้จัดอันดับ
    public function getTopRated() {
        $query = "
            SELECT c.course_id, c.course_name,
                   AVG(r.rating) as avg_rating, 
                   COUNT(r.review_id) as total_reviews,
                   
                   -- ใช้ค่าเฉลี่ยตั้งต้น 3.5 และน้ำหนักเทียบเท่า 3 รีวิว เพื่อลดผลจากรีวิวจำนวนน้อย
                   ( (AVG(r.rating) * COUNT(r.review_id)) + (3.5 * 3) ) / (COUNT(r.review_id) + 3) as weighted_score
                   
            FROM courses c
            JOIN reviews r ON c.course_id = r.course_id
    
            GROUP BY c.course_id
            HAVING total_reviews > 0 
            -- เรียงด้วยคะแนนถ่วงน้ำหนัก ส่วน avg_rating ยังคงเป็นค่าเฉลี่ยจริงสำหรับแสดงผล
            ORDER BY weighted_score DESC 
            LIMIT 5
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // รับคำค้นและค้นบางส่วนของรหัสหรือชื่อวิชา จากนั้นคืน PDOStatement ของผลลัพธ์
    public function search($keyword) {
        $query = "SELECT c.course_id, c.course_name, c.teacher_name, c.credits, cat.category_name FROM " . $this->table_name . " c LEFT JOIN categories cat ON c.category_id = cat.category_id WHERE c.course_id LIKE :keyword OR c.course_name LIKE :keyword ORDER BY c.course_id ASC";
        $stmt = $this->conn->prepare($query);
        $search_keyword = "%{$keyword}%"; $stmt->bindParam(':keyword', $search_keyword); $stmt->execute(); return $stmt;
    }
    
    // course_detail.php และ edit_course.php ส่งรหัสวิชาเพื่ออ่านข้อมูลหนึ่งรายการ
    // คืนแถวเป็น associative array หรือ false หากไม่พบ
    public function readOne($course_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE course_id = :course_id LIMIT 1";
        $stmt = $this->conn->prepare($query); $stmt->bindParam(':course_id', $course_id); $stmt->execute(); return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // admin/courses/edit_course.php ส่งรหัสวิชา ชื่อ ผู้สอน หน่วยกิต และหมวดหมู่เพื่อแก้ข้อมูล
    // คืน true เมื่อบันทึกสำเร็จ หรือ false เมื่อคำสั่งไม่สำเร็จ
    public function update($course_id, $course_name, $teacher_name, $credits, $category_id) {
        $query = "UPDATE " . $this->table_name . " SET course_name = :course_name, teacher_name = :teacher_name, credits = :credits, category_id = :category_id WHERE course_id = :course_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':course_id', $course_id); 
        $stmt->bindParam(':course_name', $course_name);
        $stmt->bindParam(':teacher_name', $teacher_name); 
        $stmt->bindParam(':credits', $credits); 
        $stmt->bindParam(':category_id', $category_id);
        return $stmt->execute();
    }

    // index.php และหน้าฟอร์มจัดการรายวิชาอ่านหมวดหมู่เรียงตามชื่อเพื่อสร้างตัวเลือก
    public function getCategories() {
        $query = "SELECT * FROM categories ORDER BY category_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // รับคำค้น รหัสหมวดหมู่ และรูปแบบการเรียง แล้วคืนผลค้นหาพร้อมข้อมูลรีวิว
    public function searchAdvanced($keyword, $category_id, $sort_by) {
        $query = "SELECT c.course_id, c.course_name, c.teacher_name, c.credits, cat.category_name,
                         COALESCE(ROUND(AVG(r.rating), 1), 0) as avg_rating,
                         MAX(r.created_at) as last_review,
                         COUNT(r.review_id) as review_count
                  FROM " . $this->table_name . " c 
                  LEFT JOIN categories cat ON c.category_id = cat.category_id 
                  LEFT JOIN reviews r ON c.course_id = r.course_id 
                  WHERE (c.course_id LIKE :keyword OR c.course_name LIKE :keyword)";
        
        if (!empty($category_id)) {
            $query .= " AND c.category_id = :category_id";
        }
        
        $query .= " GROUP BY c.course_id";

        // most_reviews เรียงตามจำนวนรีวิว และ newest เรียงตามวันที่รีวิวล่าสุด
        // ค่าอื่นจะเรียงตามรหัสวิชา
        if ($sort_by === 'most_reviews') {
            $query .= " ORDER BY review_count DESC, c.course_id ASC";
        } elseif ($sort_by === 'newest') {
            $query .= " ORDER BY last_review DESC, c.course_id ASC";
        } else {
            $query .= " ORDER BY c.course_id ASC"; // ใช้รหัสวิชาเป็นลำดับเริ่มต้น
        }

        $stmt = $this->conn->prepare($query);
        $search_keyword = "%{$keyword}%";
        $stmt->bindParam(':keyword', $search_keyword);
        
        if (!empty($category_id)) {
            $stmt->bindParam(':category_id', $category_id);
        }
        
        $stmt->execute();
        return $stmt;
    }
    // admin/index.php อ่านจำนวนวิชาทั้งหมดในตารางเพื่อแสดงบนแดชบอร์ด
    public function getTotal() {
    $stmt = $this->conn->query("SELECT COUNT(*) FROM " . $this->table_name);
    return $stmt->fetchColumn();
}

    // index.php ส่งคำค้นและหมวดหมู่เพื่อให้นับผลลัพธ์ทั้งหมดก่อนแบ่งหน้า
    // คืนจำนวนวิชาที่ไม่ซ้ำเป็นจำนวนเต็ม
    public function countSearchAdvanced($keyword, $category_id) {
        $query = "SELECT COUNT(DISTINCT c.course_id) as total
                  FROM " . $this->table_name . " c 
                  WHERE (c.course_id LIKE :keyword OR c.course_name LIKE :keyword)";
        if (!empty($category_id)) {
            $query .= " AND c.category_id = :category_id";
        }
        $stmt = $this->conn->prepare($query);
        $search_keyword = "%{$keyword}%";
        $stmt->bindParam(':keyword', $search_keyword);
        if (!empty($category_id)) {
            $stmt->bindParam(':category_id', $category_id);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['total'];
    }

    // index.php ส่งคำค้น หมวดหมู่ รูปแบบเรียง จำนวนแถว และตำแหน่งเริ่มต้นเพื่ออ่านผลหนึ่งหน้า
    // คืน PDOStatement ของวิชาที่ตรงเงื่อนไข
    public function searchAdvancedPaginated($keyword, $category_id, $sort_by, $limit = 6, $offset = 0) {
        $query = "SELECT c.course_id, c.course_name, c.teacher_name, c.credits, cat.category_name,
                         COALESCE(ROUND(AVG(r.rating), 1), 0) as avg_rating,
                         MAX(r.created_at) as last_review,
                         COUNT(r.review_id) as review_count
                  FROM " . $this->table_name . " c 
                  LEFT JOIN categories cat ON c.category_id = cat.category_id 
                  LEFT JOIN reviews r ON c.course_id = r.course_id 
                  WHERE (c.course_id LIKE :keyword OR c.course_name LIKE :keyword)";
        
        if (!empty($category_id)) {
            $query .= " AND c.category_id = :category_id";
        }
        $query .= " GROUP BY c.course_id";

        if ($sort_by === 'most_reviews') {
            $query .= " ORDER BY review_count DESC, c.course_id ASC";
        } elseif ($sort_by === 'newest') {
            $query .= " ORDER BY last_review DESC, c.course_id ASC";
        } elseif ($sort_by === 'H_credits') {
            $query .= " ORDER BY c.credits DESC, c.course_id ASC"; // เรียงตามหน่วยกิตมากสุด และถ้าเท่ากันให้เรียงตามรหัสวิชาลดหลั่น
        } elseif ($sort_by === 'L_credits') {
            $query .= " ORDER BY c.credits ASC, c.course_id ASC"; // เรียงตามหน่วยกิตน้อยสุด และถ้าเท่ากันให้เรียงตามรหัสวิชาลดหลั่น
        }
        else {
            $query .= " ORDER BY c.course_id ASC";
        }

        // LIMIT เลือกจำนวนแถว และ OFFSET ข้ามแถวก่อนหน้าเพื่อเปิดหน้าผลลัพธ์ที่ต้องการ
        $query .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $search_keyword = "%{$keyword}%";
        $stmt->bindParam(':keyword', $search_keyword);
        if (!empty($category_id)) {
            $stmt->bindParam(':category_id', $category_id);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt;
    }
}
?>