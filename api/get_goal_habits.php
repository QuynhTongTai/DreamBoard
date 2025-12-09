<?php
// Tắt lỗi HTML để trả về JSON sạch
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    session_start();

    // 1. SỬA ĐƯỜNG DẪN Ở ĐÂY (Dùng ../ thay vì ../../)
    // Từ folder /api/ lùi ra 1 cấp là tới folder gốc DreamBoard
    $path_db = __DIR__ . '/../config/database.php';
    $path_model = __DIR__ . '/../app/models/JournalModel.php';

    if (!file_exists($path_db)) {
        throw new Exception("File not found: " . $path_db);
    }
    
    require_once $path_db;
    require_once $path_model;

    if (empty($_SESSION['user_id']) || empty($_GET['goal_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing params']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $goal_id = $_GET['goal_id'];
    $today = date('Y-m-d');

    $db = new Database();
    $conn = $db->connect();

    // Lấy habits và kiểm tra xem hôm nay đã làm chưa
    $query = "SELECT h.habit_id, h.title, hl.log_id as is_done 
              FROM habits h 
              LEFT JOIN habit_logs hl ON h.habit_id = hl.habit_id AND hl.check_date = :today
              WHERE h.goal_id = :gid AND h.user_id = :uid";

    $stmt = $conn->prepare($query);
    $stmt->execute([':gid' => $goal_id, ':uid' => $user_id, ':today' => $today]);
    $habits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $habits]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>