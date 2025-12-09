<?php
// File: api/check_habit.php
require_once __DIR__ . '/../app/models/JournalModel.php';

// Đảm bảo trả về JSON kể cả khi có lỗi
header('Content-Type: application/json');
error_reporting(0); // Tắt lỗi PHP hiển thị ra làm hỏng JSON

try {
    $habit_id = $_POST['habit_id'] ?? 0;
    $date = date('Y-m-d');

    if (!$habit_id) {
        throw new Exception('Missing habit ID');
    }

    $model = new JournalModel();

    // BƯỚC 1: Toggle trạng thái (Check/Uncheck) trong DB
    // Hàm này trả về 'checked' hoặc 'unchecked'
    $status = $model->toggleHabit($habit_id, $date);

    // BƯỚC 2: Lấy Goal ID từ Habit ID
    // (Ta cần biết thói quen này thuộc mục tiêu nào để tính lại %)
    $goal_id = $model->getGoalIdByHabit($habit_id);

    // BƯỚC 3: Tính toán lại Progress mới
    $new_progress = 0;
    if ($goal_id) {
        // Hàm này sẽ tính toán dựa trên Start/End date và cập nhật DB luôn
        $new_progress = $model->calculateGoalProgress($goal_id);
    }

    // Trả kết quả về cho JS
    echo json_encode([
        'status' => 'success',
        'action' => $status,          // 'checked' hoặc 'unchecked'
        'new_progress' => $new_progress // Số % mới (ví dụ: 45)
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>