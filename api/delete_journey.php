<?php
require_once '../app/models/JournalModel.php';
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) { echo json_encode(['status'=>'error']); exit; }

// Lấy dữ liệu từ JS gửi lên
$log_id = $_POST['log_id'] ?? 0;
$goal_id = $_POST['goal_id'] ?? 0; // <--- Cần nhận thêm cái này
$user_id = $_SESSION['user_id'];

$model = new JournalModel();

// 1. Xóa bài viết
if ($model->deleteLog($log_id, $user_id)) {
    
    // 2. Tính lại % Max mới cho Goal
    $newMax = $model->updateGoalProgressToMax($goal_id);

    // 3. Trả về kết quả kèm số % mới
    echo json_encode([
        'status' => 'success', 
        'new_progress' => $newMax
    ]);
} else {
    echo json_encode(['status'=>'error', 'message'=>'Failed to delete']);
}
?>