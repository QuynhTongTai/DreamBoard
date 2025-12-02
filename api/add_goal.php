<?php
require_once __DIR__ . '/../app/models/JournalModel.php';
if (session_status() == PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$title = $_POST['title'] ?? '';
// 1. Lấy thêm topic_id từ dữ liệu gửi lên
$topic_id = $_POST['topic_id'] ?? null;

if (trim($title) === '') {
    echo json_encode(['status'=>'error','message'=>'Title required']);
    exit;
}

$model = new JournalModel();

// 2. Truyền đủ 3 tham số vào hàm (user_id, title, topic_id)
$model->addGoal($user_id, $title, $topic_id);

echo json_encode(['status'=>'success']);
exit;