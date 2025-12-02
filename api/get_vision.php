<?php
// Tắt hiển thị lỗi HTML để tránh làm hỏng JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Gọi Controller từ thư mục cha
require_once '../app/controllers/VisionController.php';

$controller = new VisionController();
$controller->getBoardData();
?>