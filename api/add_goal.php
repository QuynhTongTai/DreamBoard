<?php
// File: api/add_goal.php

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
    
    // 2. Lấy dữ liệu cơ bản
    $title = $_POST['title'] ?? '';
    $topic_name = $_POST['topic_name'] ?? '';

    // [CẬP NHẬT QUAN TRỌNG] Lấy mảng habits
    // Vì bên HTML đặt name="daily_habits[]", nên PHP sẽ nhận được một mảng
    $daily_habits = $_POST['daily_habits'] ?? []; 
    
    // Kiểm tra an toàn: Nếu không phải mảng (lỗi gì đó) thì ép về mảng rỗng
    if (!is_array($daily_habits)) {
        $daily_habits = [];
    }

    // Lấy ngày tháng
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

    // 3. Validate Title
    if (trim($title) === '') {
        throw new Exception('Goal title is required');
    }

    // 4. Xử lý Logic
    $model = new JournalModel();

    // Tìm hoặc tạo Topic
    $topic_id = $model->getOrCreateTopic($user_id, $topic_name);

    // [CẬP NHẬT] Truyền mảng $daily_habits vào hàm addGoal
    // (Bên JournalModel bạn đã sửa để nhận array rồi nên ở đây truyền array là đúng)
    $result = $model->addGoal($user_id, $title, $topic_id, $daily_habits, $start_date, $end_date);

    if ($result) {
        // Trả về success
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