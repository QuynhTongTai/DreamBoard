<?php
class Database {
    // 1. Thông tin cấu hình (Thay đổi nếu bạn dùng mật khẩu khác)
    private $host = "localhost";
    private $db_name = "dreamboard_db"; // Tên database bạn vừa tạo
    private $username = "root";         // Tên đăng nhập mặc định của XAMPP/WAMP
    private $password = "";             // Mật khẩu mặc định thường để trống
    public $conn;

    public function connect() {
        $this->conn = null;

        try {
            // Data Source Name(DSN)
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            // PHP Data Objects: PDO
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Cấu hình để PDO báo lỗi dạng Exception 
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Cấu hình chế độ lấy dữ liệu mặc định là mảng kết hợp (Associative Array)
            // Ví dụ: $row['username'] thay vì $row[0]
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch(PDOException $exception) {
            echo "Lỗi kết nối Database: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>