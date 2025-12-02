<?php
// LƯU Ý QUAN TRỌNG: Thêm dấu ../ ở đầu đường dẫn
// Ý nghĩa: Từ thư mục 'api', đi ra ngoài 1 cấp, rồi mới vào 'app/...'
require_once '../app/controllers/JournalController.php';

$controller = new JournalController();
$controller->getGoalLogs();
?>