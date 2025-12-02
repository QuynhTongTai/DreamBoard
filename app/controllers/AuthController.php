<?php
require_once 'app/models/UserModel.php';

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
}
?>