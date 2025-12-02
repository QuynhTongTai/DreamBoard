<?php
// index.php

// 1. Gọi file Controller
require_once 'app/controllers/HomeController.php';

// 2. Khởi tạo Controller
$home = new HomeController();

// 3. Chạy hàm index để hiển thị trang
$home->index();
?>