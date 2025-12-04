<?php
// Tắt hiển thị lỗi trực tiếp ra màn hình (để tránh làm hỏng JSON)
error_reporting(0); 

header('Content-Type: application/json');

// Gọi Model
require_once __DIR__ . '/../app/models/JournalModel.php';

// Khởi động session
if (session_status() == PHP_SESSION_NONE) session_start();

try {
    // 1. Kiểm tra đăng nhập
    if (empty($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    $user_id = $_SESSION['user_id'];
    
    // 2. Lấy dữ liệu
    $title = $_POST['title'] ?? '';
    $topic_name = $_POST['topic_name'] ?? '';

    // 3. Validate
    if (trim($title) === '') {
        throw new Exception('Goal title is required');
    }

    // 4. Xử lý Logic
    $model = new JournalModel();

    // Tìm hoặc tạo Topic
    $topic_id = $model->getOrCreateTopic($user_id, $topic_name);

    // Thêm Goal
    $result = $model->addGoal($user_id, $title, $topic_id);

    if ($result) {
        // Trả về success đơn giản
        echo json_encode(['status' => 'success']);
    } else {
        throw new Exception('Database error');
    }

} catch (Exception $e) {
    // Trả về lỗi
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}
?>