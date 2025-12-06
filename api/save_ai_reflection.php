<?php
// File: api/save_ai_reflection.php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/models/JournalModel.php';

// Nhận dữ liệu JSON từ JS
$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['log_id']) || empty($input['analysis'])) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu dữ liệu']);
    exit;
}

try {
    $model = new JournalModel();
    $result = $model->saveAiReflection(
        $input['log_id'],
        $input['analysis'],
        $input['advice'],
        $input['quote']
    );

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi SQL']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>