<?php
// File: api/check_habit.php
require_once __DIR__ . '/../app/models/JournalModel.php';
header('Content-Type: application/json');

$habit_id = $_POST['habit_id'] ?? 0;
$date = date('Y-m-d');

if(!$habit_id) {
    echo json_encode(['status'=>'error']); exit;
}

$model = new JournalModel();
$status = $model->toggleHabit($habit_id, $date);

echo json_encode(['status'=>'success', 'action' => $status]);
?>