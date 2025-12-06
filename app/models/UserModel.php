<?php
// Gọi file cấu hình database
require_once __DIR__ . '/../../config/database.php';

class UserModel
{
    private $conn;
    private $table_name = "users";

    public function __construct()
    {
        // Kết nối database ngay khi tạo Model
        $database = new Database();
        $this->conn = $database->connect();
    }

    // 1. Hàm ĐĂNG KÝ (Register)
    public function register($username, $email, $password)
    {
        // Câu lệnh SQL
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, email, password) 
                  VALUES (:username, :email, :password)";

        // Chuẩn bị câu lệnh (Prepare Statement)
        $stmt = $this->conn->prepare($query);

        // Làm sạch dữ liệu đầu vào (Tránh mã độc XSS cơ bản)
        $username = htmlspecialchars(strip_tags($username));
        $email = htmlspecialchars(strip_tags($email));

        // --- QUAN TRỌNG: Mã hóa mật khẩu ---
        // Không bao giờ lưu password thô. Dùng BCRYPT để mã hóa.
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Gán dữ liệu vào các tham số (:username, ...)
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password_hash);

        // Thực thi và trả về kết quả
        if ($stmt->execute()) {
            return true; // Đăng ký thành công
        }
        return false; // Đăng ký thất bại
    }

    // 2. Hàm KIỂM TRA EMAIL ĐÃ TỒN TẠI CHƯA
    public function isEmailExists($email)
    {
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // Nếu tìm thấy dòng nào (> 0) nghĩa là email đã có
        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }

    // 3. Hàm KIỂM TRA USERNAME ĐÃ TỒN TẠI CHƯA
    public function isUsernameExists($username)
    {
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }

    // 4. Hàm ĐĂNG NHẬP (Login)
    public function login($email, $password)
    {
        // Bước 1: Tìm user bằng email
        // Bỏ chữ 'full_name' đi
        $query = "SELECT user_id, username, password, avatar 
          FROM " . $this->table_name . " 
          WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // Bước 2: Nếu tìm thấy user
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC); // Lấy dữ liệu user ra
            $hashed_password = $row['password'];   // Lấy mật khẩu đã mã hóa trong DB

            // Bước 3: So sánh mật khẩu nhập vào với mật khẩu mã hóa
            if (password_verify($password, $hashed_password)) {
                // Xóa mật khẩu khỏi mảng trước khi trả về (để bảo mật)
                unset($row['password']);
                return $row; // Trả về thông tin user (để lưu vào Session)
            }
        }

        return false; // Sai email hoặc sai mật khẩu
    }

    // 5. Hàm Lấy thông tin chi tiết User (Dùng cho trang Profile)
    public function getUserById($user_id)
    {
        $query = "SELECT user_id, username, email, avatar, full_name, created_at 
                  FROM " . $this->table_name . " 
                  WHERE user_id = :user_id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function updateAvatar($user_id, $avatar_path) {
        $query = "UPDATE " . $this->table_name . " SET avatar = :avatar WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        
        // Bind dữ liệu
        $stmt->bindParam(':avatar', $avatar_path);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
    // --- [MỚI] CÁC HÀM XỬ LÝ OTP (QUÊN MẬT KHẨU) ---

    // 1. Lưu mã OTP và thời gian hết hạn
    public function saveOtp($email, $otp, $expiry) {
        // Kiểm tra email có tồn tại không trước
        if (!$this->isEmailExists($email)) {
            return false; 
        }

        $query = "UPDATE " . $this->table_name . " SET otp_code = :otp, otp_expiry = :expiry WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        
        // Bind dữ liệu
        $stmt->bindParam(':otp', $otp);
        $stmt->bindParam(':expiry', $expiry);
        $stmt->bindParam(':email', $email);
        
        return $stmt->execute();
    }

    // 2. Lấy thông tin User dựa trên Email và OTP để kiểm tra
    public function getUserByEmailAndOtp($email, $otp) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email AND otp_code = :otp LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':otp', $otp);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 3. Cập nhật mật khẩu mới và xóa OTP
    public function updatePassword($email, $newPassword) {
        // Mã hóa mật khẩu mới
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        
        // Cập nhật pass, xóa OTP để không dùng lại được
        $query = "UPDATE " . $this->table_name . " 
                  SET password = :pass, otp_code = NULL, otp_expiry = NULL 
                  WHERE email = :email";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pass', $hashed);
        $stmt->bindParam(':email', $email);
        
        return $stmt->execute();
    }
}
?>