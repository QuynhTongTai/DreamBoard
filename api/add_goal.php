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

    // [MỚI] Lấy thêm dữ liệu Thói quen và Ngày tháng từ Form
    $daily_habit = $_POST['daily_habit'] ?? '';
    // Nếu ngày rỗng thì để null, tránh lỗi định dạng Date trong SQL
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

    // 3. Validate
    if (trim($title) === '') {
        throw new Exception('Goal title is required');
    }

    // 4. Xử lý Logic
    $model = new JournalModel();

    // Tìm hoặc tạo Topic
    $topic_id = $model->getOrCreateTopic($user_id, $topic_name);

    // [CẬP NHẬT] Gọi hàm addGoal với đầy đủ 6 tham số (để khớp với bên Model vừa sửa)
    // Thứ tự: ($user_id, $title, $topic_id, $habit_title, $start_date, $end_date)
    $result = $model->addGoal($user_id, $title, $topic_id, $daily_habit, $start_date, $end_date);

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