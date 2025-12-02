<?php
// app/controllers/HomeController.php

class HomeController {
    public function index() {
        // Khởi động session để Topbar biết user đã đăng nhập chưa
        if (session_status() == PHP_SESSION_NONE) session_start();

        // 1. Phần ĐẦU (Head)
        include 'app/views/layouts/head.php';
        
        // 2. CSS Riêng cho trang Home
        echo '<link rel="stylesheet" href="assets/css/home.css">'; 
        
        // 3. Thanh Menu (Topbar)
        include 'app/views/layouts/topbar.php';

        // 4. Nội dung chính (Phần HTML 3 phần Hero/Features/How-to mà chúng ta vừa làm)
        include 'app/views/home_view.php';

        // 5. JS Riêng cho trang Home
        echo '<script src="assets/js/home.js"></script>';
        
        // 6. Chân trang (Footer)
        // Lưu ý: Dùng đường dẫn tương đối từ index.php sẽ dễ hơn __DIR__
        include 'app/views/layouts/footer.php'; 
    }
}
?>