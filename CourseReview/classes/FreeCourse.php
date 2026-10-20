<?php
// จัดการข้อมูลคอร์สออนไลน์ฟรี รวมถึงการค้นหาและอัปโหลด/ลบรูปปก
class FreeCourse {
    private $conn;
    private $table_name = "free_courses";

    // รับการเชื่อมต่อ PDO จากหน้าที่สร้างคลาส เพื่อใช้ในเมธอดทั้งหมด
    public function __construct($db) { 
        $this->conn = $db; 
    }

    // course_free.php และ admin/free_courses/manage_free_courses.php อ่านรายการคอร์ส
    // course_free.php ส่งคำค้น หมวดหมู่ ภาษา และตัวกรองใบรับรอง (เลือกใส่ได้) เพื่อกรองผล
    // คืน PDOStatement ของคอร์สที่ตรงเงื่อนไข โดยเรียงรายการใหม่ก่อน
    public function readAll($keyword = "", $category = "", $language = "", $has_cert = "") {
        $query = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        
        if (!empty($keyword)) {
            $query .= " AND (title LIKE :keyword OR provider LIKE :keyword)";
        }
        if (!empty($category)) {
            $query .= " AND category = :category";
        }
        if (!empty($language)) {
            $query .= " AND language = :language";
        }
        if ($has_cert !== "") {
            $query .= " AND has_certificate = :has_cert";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($keyword)) {
            $search_keyword = "%{$keyword}%";
            $stmt->bindParam(':keyword', $search_keyword);
        }
        if (!empty($category)) { $stmt->bindParam(':category', $category); }
        if (!empty($language)) { $stmt->bindParam(':language', $language); }
        if ($has_cert !== "") { $stmt->bindParam(':has_cert', $has_cert); }
        
        $stmt->execute();
        return $stmt;
    }

    // admin/free_courses/edit_free_course.php ส่งรหัสคอร์สเพื่ออ่านข้อมูลหนึ่งรายการ
    // คืน associative array หรือ false หากไม่พบ
    public function readOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE course_id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // admin/free_courses/add_free_course.php ส่งชื่อ ผู้ให้บริการ หมวดหมู่ ระดับ ระยะเวลา ภาษา
    // ใบรับรอง URL และชื่อไฟล์รูปปกเพื่อเพิ่มคอร์ส
    // คืนรหัสรายการใหม่เมื่อบันทึกสำเร็จ หรือ false เมื่อบันทึกไม่สำเร็จ
    public function create($title, $provider, $category, $level, $duration, $language, $has_cert, $url, $cover_image) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (title, provider, category, level, duration, language, has_certificate, course_url, cover_image) 
                  VALUES (:title, :provider, :category, :level, :duration, :language, :has_cert, :url, :cover_image)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':provider', $provider);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':level', $level);
        $stmt->bindParam(':duration', $duration);
        $stmt->bindParam(':language', $language);
        $stmt->bindParam(':has_cert', $has_cert);
        $stmt->bindParam(':url', $url);
        $stmt->bindParam(':cover_image', $cover_image);
        
        // คืนรหัสรายการที่ฐานข้อมูลสร้างให้เมื่อเพิ่มข้อมูลสำเร็จ
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // admin/free_courses/edit_free_course.php ส่งรหัสคอร์สและค่าจากฟอร์มเพื่อแก้ข้อมูล
    // เปลี่ยนรูปปกเฉพาะเมื่อส่งชื่อไฟล์ใหม่
    // คืน true เมื่ออัปเดตสำเร็จ หรือ false เมื่อไม่สำเร็จ
    public function update($id, $title, $provider, $category, $level, $duration, $language, $has_cert, $url, $cover_image) {
        $query = "UPDATE " . $this->table_name . " 
                  SET title = :title, provider = :provider, category = :category, 
                      level = :level, duration = :duration, language = :language, 
                      has_certificate = :has_cert, course_url = :url";
                      
        if (!empty($cover_image)) {
            $query .= ", cover_image = :cover_image";
        }
        $query .= " WHERE course_id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':provider', $provider);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':level', $level);
        $stmt->bindParam(':duration', $duration);
        $stmt->bindParam(':language', $language);
        $stmt->bindParam(':has_cert', $has_cert);
        $stmt->bindParam(':url', $url);
        $stmt->bindParam(':id', $id);
        
        if (!empty($cover_image)) {
            $stmt->bindParam(':cover_image', $cover_image);
        }
        return $stmt->execute();
    }

    // manage_free_courses.php ส่งรหัสคอร์สเพื่อลบรูปปกเฉพาะรายการและลบข้อมูลคอร์ส
    // คืน true เมื่อคำสั่งลบข้อมูลสำเร็จ หรือ false เมื่อไม่สำเร็จ
    public function delete($id) {
        // อ่านข้อมูลก่อนลบเพื่อให้ทราบชื่อไฟล์รูปปก
        $courseData = $this->readOne($id);
        // ไม่ลบภาพ no_image.png ที่ใช้ร่วมกัน และตรวจว่ารูปเฉพาะรายการมีอยู่ก่อนลบ
        if ($courseData && !empty($courseData['cover_image']) && $courseData['cover_image'] !== 'no_image.png') {
            $file_path = __DIR__ . '/../assets/uploads/' . $courseData['cover_image'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // ลบแถวคอร์สหลังจัดการรูปปกแล้ว
        $query = "DELETE FROM " . $this->table_name . " WHERE course_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // admin/index.php อ่านจำนวนคอร์สฟรีทั้งหมดเพื่อแสดงบนแดชบอร์ด
    public function getTotal() {
    $stmt = $this->conn->query("SELECT COUNT(*) FROM " . $this->table_name);
    return $stmt->fetchColumn();
    }

    // admin/free_courses/add_free_course.php และ edit_free_course.php ส่งข้อมูลไฟล์เพื่อบันทึกรูปปก
    // คืนชื่อไฟล์ใหม่เมื่อย้ายไฟล์สำเร็จ หรือ false หากไฟล์ไม่ผ่านเงื่อนไข/ย้ายไม่สำเร็จ
    public function uploadImage($file) {
    // เริ่มประมวลผลเมื่อมีไฟล์และ PHP แจ้งว่าอัปโหลดสำเร็จ
    if (isset($file) && $file['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        // ทำให้นามสกุลเป็นตัวพิมพ์เล็กก่อนตรวจรายการที่อนุญาต
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // รับเฉพาะนามสกุลที่กำหนดและสร้างชื่อใหม่ให้ไฟล์
        if (in_array($file_ext, $allowed_ext)) {
            $new_name = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $upload_path = __DIR__ . '/../assets/uploads/' . $new_name;
            
            // คืนชื่อไฟล์เพื่อให้หน้าที่เรียกนำไปบันทึกต่อ เมื่อย้ายไฟล์สำเร็จ
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                return $new_name;
            }
        }
    }
    return false;
}
}
?>