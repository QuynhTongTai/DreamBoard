<?php
// File: api/add_journey.php
require_once __DIR__ . '/../app/controllers/JournalController.php';

// Tắt hiển thị lỗi HTML để tránh làm hỏng JSON, nhưng log lỗi lại
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    $controller = new JournalController();
    $controller->addJourney();
} catch (Exception $e) {
    // Trả về JSON lỗi nếu có sự cố bất ngờ
    echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>