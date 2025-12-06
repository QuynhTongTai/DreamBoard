<?php
// File: login.php (Router điều hướng)
session_start();

// Gọi Controller
require_once 'app/controllers/AuthController.php';

$auth = new AuthController();

// Lấy yêu cầu hành động từ URL (ví dụ: login.php?action=send_otp)
$action = $_GET['action'] ?? 'login'; 

switch ($action) {
    case 'login':
        $auth->login();
        break;

    case 'register':
        $auth->register();
        break;
        
    case 'logout':
        $auth->logout();
        break;

    // --- CÁC HÀM XỬ LÝ QUÊN MẬT KHẨU (GỌI AJAX) ---
    case 'send_otp':
        $auth->sendOtp(); // Hàm này trả về JSON
        break;

    case 'verify_otp':
        $auth->verifyOtp(); // Hàm này trả về JSON
        break;

    case 'reset_password':
        $auth->resetPassword(); // Hàm này trả về JSON
        break;

    default:
        $auth->login(); // Mặc định hiện form đăng nhập
        break;
}
?>