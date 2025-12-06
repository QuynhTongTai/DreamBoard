<?php
require_once __DIR__ . '/../models/UserModel.php';

// Nhúng PHPMailer (Đảm bảo bạn đã cài composer require phpmailer/phpmailer)
require_once __DIR__ . '/../../vendor/autoload.php';

// 3. Khai báo namespace của PHPMailer để sử dụng bên dưới
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
class AuthController
{
    private $userModel;
    public function __construct()
    {
        $this->userModel = new UserModel();
        // Khởi động session để lưu trạng thái đăng nhập
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    // --- XỬ LÝ ĐĂNG KÝ ---
    public function register()
    {
        $error = ''; // Biến chứa lỗi

        // Nếu người dùng nhấn nút "Đăng Ký" (Gửi form)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Lấy dữ liệu từ form
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            // 1. Kiểm tra dữ liệu nhập
            if ($password != $confirm_password) {
                $error = "Mật khẩu xác nhận không khớp!";
            } elseif ($this->userModel->isEmailExists($email)) {
                $error = "Email này đã được sử dụng!";
            } elseif ($this->userModel->isUsernameExists($username)) {
                $error = "Tên đăng nhập đã tồn tại!";
            } else {
                // 2. Nếu không có lỗi -> Gọi Model để tạo user
                if ($this->userModel->register($username, $email, $password)) {
                    // Đăng ký thành công -> Chuyển sang trang login
                    header("Location: login.php?msg=registered");
                    exit;
                } else {
                    $error = "Đã có lỗi xảy ra, vui lòng thử lại.";
                }
            }
        }

        // Hiển thị giao diện đăng ký (kèm thông báo lỗi nếu có)
        include 'app/views/auth/register_view.php';
    }

    // --- XỬ LÝ ĐĂNG NHẬP ---
    public function login()
    {
        $error = '';

        // Nếu người dùng nhấn nút "Đăng Nhập"
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];

            // 1. Gọi Model kiểm tra
            $loggedInUser = $this->userModel->login($email, $password);

            if ($loggedInUser) {
                $_SESSION['user_id'] = $loggedInUser['user_id'];
                $_SESSION['username'] = $loggedInUser['username'];
                $_SESSION['avatar'] = $loggedInUser['avatar'];

                // Đã xóa dòng full_name

                header("Location: index.php");
                exit;
            } else {
                $error = "Email hoặc mật khẩu không chính xác.";
            }
        }

        // Hiển thị giao diện đăng nhập
        include 'app/views/auth/login_view.php';
    }

    // --- XỬ LÝ ĐĂNG XUẤT ---
    public function logout()
    {
        session_destroy(); // Xóa sạch session
        header("Location: login.php");
        exit;
    }
    // --- [MỚI] CHỨC NĂNG QUÊN MẬT KHẨU (AJAX HANDLERS) ---
    // ============================================================

    // 1. API: Gửi OTP
    public function sendOtp()
    {
        header('Content-Type: application/json'); // Trả về JSON cho JS

        $email = $_POST['email'] ?? '';
        if (empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập email!']);
            exit;
        }

        // Tạo OTP 6 số
        $otp = rand(100000, 999999);
        // Hết hạn sau 5 phút
        $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        // Gọi Model lưu OTP
        if ($this->userModel->saveOtp($email, $otp, $expiry)) {
            // Gửi mail thật
            if ($this->sendMailSMTP($email, $otp)) {
                echo json_encode(['status' => 'success', 'message' => 'Mã OTP đã được gửi đến email của bạn!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi gửi mail. Vui lòng thử lại sau.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Email không tồn tại trong hệ thống!']);
        }
        exit;
    }

    // 2. API: Xác thực OTP
    public function verifyOtp()
    {
        header('Content-Type: application/json');

        $email = $_POST['email'] ?? '';
        $otpInput = $_POST['otp'] ?? '';

        $user = $this->userModel->getUserByEmailAndOtp($email, $otpInput);

        if ($user) {
            // Kiểm tra hết hạn
            if (strtotime($user['otp_expiry']) < time()) {
                echo json_encode(['status' => 'error', 'message' => 'Mã OTP đã hết hạn! Vui lòng lấy mã mới.']);
            } else {
                echo json_encode(['status' => 'success', 'message' => 'Xác thực thành công!']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Mã OTP không chính xác!']);
        }
        exit;
    }

    // 3. API: Đổi mật khẩu mới
    public function resetPassword()
    {
        header('Content-Type: application/json');

        $email = $_POST['email'] ?? '';
        $newPass = $_POST['password'] ?? '';

        if (strlen($newPass) < 6) {
            echo json_encode(['status' => 'error', 'message' => 'Mật khẩu phải từ 6 ký tự trở lên!']);
            exit;
        }

        if ($this->userModel->updatePassword($email, $newPass)) {
            echo json_encode(['status' => 'success', 'message' => 'Đổi mật khẩu thành công! Vui lòng đăng nhập lại.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi Database. Không thể đổi mật khẩu.']);
        }
        exit;
    }

    // --- HÀM HỖ TRỢ GỬI MAIL (PHPMailer) ---
    private function sendMailSMTP($toEmail, $otp)
    {
        $mail = new PHPMailer(true);
        try {
            // Cấu hình Server
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'dreamboard47@gmail.com'; // <--- Email của bạn
            $mail->Password = 'ccgc vgvq dbzu wqjx';    // <--- App Password của bạn
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            // Người gửi & Người nhận
            $mail->setFrom('dreamboard47@gmail.com', 'DreamBoard Security');
            $mail->addAddress($toEmail);

            // Nội dung
            $mail->isHTML(true);
            $mail->Subject = 'Reset Password OTP - DreamBoard';
            $mail->Body = "
                <div style='font-family: sans-serif; padding: 20px; background: #f3e8ff; text-align: center;'>
                    <div style='background: #fff; padding: 30px; border-radius: 10px; max-width: 500px; margin: auto; box-shadow: 0 5px 15px rgba(0,0,0,0.1);'>
                        <h2 style='color: #6b5bff'>🔒 Yêu cầu đổi mật khẩu</h2>
                        <p>Mã xác thực của bạn là:</p>
                        <h1 style='color: #4c3b9b; letter-spacing: 5px; font-size: 32px; margin: 20px 0;'>$otp</h1>
                        <p style='color: #888; font-size: 13px;'>Mã này sẽ hết hạn sau 5 phút. Nếu bạn không yêu cầu, vui lòng bỏ qua email này.</p>
                    </div>
                </div>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            // error_log("Mailer Error: " . $mail->ErrorInfo); // Bỏ comment để debug nếu cần
            return false;
        }
    }
}
?>