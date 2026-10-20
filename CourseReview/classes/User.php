<?php
// จัดการบัญชีผู้ใช้ ได้แก่ การเข้าสู่ระบบ การสมัคร และการเปลี่ยนรหัสผ่าน
class User {
    private $conn;
    private $table_name = "users";

    // รับการเชื่อมต่อ PDO จากหน้าที่สร้างคลาส เพื่อใช้กับตาราง users
    public function __construct($db) {
        $this->conn = $db;
    }

    // login.php ส่งชื่อผู้ใช้และรหัสผ่านเพื่อตรวจบัญชี
    // คืนข้อมูลบัญชีเมื่อผ่านการตรวจ หรือ false เมื่อไม่พบชื่อ/รหัสผ่านไม่ตรง
    public function login($username, $password) {
        $query = "SELECT user_id, username, password, role FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            // ตรวจรหัสกับ hash หรือเทียบตรงเพื่อรองรับข้อมูลบัญชีรูปแบบเดิม
            if(password_verify($password, $row['password']) || $password === $row['password']) {
                return $row;
            }
        }
        return false;
    }

    // register.php ส่งชื่อผู้ใช้และรหัสผ่านเพื่อสมัครบัญชีใหม่
    // คืน "exists" เมื่อชื่อนี้ถูกใช้แล้ว, "success" เมื่อเพิ่มสำเร็จ หรือ "error" เมื่อเพิ่มไม่สำเร็จ
    public function register($username, $password) {
        // ตรวจชื่อผู้ใช้ซ้ำก่อนสร้างบัญชี
        $check_query = "SELECT user_id FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':username', $username);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            return "exists";
        }

        // แปลงรหัสผ่านเป็น hash ก่อนบันทึก และกำหนดบทบาทบัญชีใหม่เป็น student
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO " . $this->table_name . " (username, password, role) VALUES (:username, :password, 'student')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);

        if ($stmt->execute()) {
            return "success";
        }
        return "error";
    }
    
    // change_password.php ส่งรหัสผู้ใช้ รหัสผ่านเดิม และรหัสผ่านใหม่เพื่อเปลี่ยนรหัส
    // คืน true เมื่อเปลี่ยนสำเร็จ หรือ false หากบัญชี/รหัสเดิมไม่ถูกต้องหรือคำสั่งไม่สำเร็จ
    public function changePassword($user_id, $current_password, $new_password) {
        // อ่านรหัสผ่านที่บันทึกไว้ของบัญชีนี้เพื่อตรวจรหัสเดิม
        $query = "SELECT password FROM " . $this->table_name . " WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            // ดำเนินการต่อเมื่อรหัสเดิมผ่านการตรวจ
            if (password_verify($current_password, $row['password']) || $current_password === $row['password']) {
                // แปลงรหัสใหม่เป็น hash ก่อนอัปเดตข้อมูลบัญชี
                $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE " . $this->table_name . " SET password = :password WHERE user_id = :user_id";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bindParam(':password', $new_hashed);
                $update_stmt->bindParam(':user_id', $user_id);
                
                return $update_stmt->execute();
            }
        }
        return false;
    }

    // อ่านจำนวนบัญชีทั้งหมดในตาราง และคืนค่าจำนวนจากฐานข้อมูล
    public function getTotal() {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM " . $this->table_name);
        return $stmt->fetchColumn();
    }
}
?>