<?php
require_once __DIR__ . '/../models/FutureModel.php';

class FutureController {

    // API: Lưu thư
    public function saveLetter() {
        header('Content-Type: application/json');
        session_start();
        $user_id = $_SESSION['user_id'] ?? 0;

        if (!$user_id) { echo json_encode(['status'=>'error', 'message'=>'Unauthorized']); exit; }

        $title = $_POST['subject'] ?? 'Letter to Future Self';
        $message = $_POST['message'] ?? '';
        $date = $_POST['openDate'] ?? '';
        $time = $_POST['openTime'] ?? '09:00';
        $email = $_POST['email'] ?? '';
        $mood = $_POST['moodTag'] ?? '';

        // Gộp ngày và giờ thành datetime
        $delivery_time = $date . ' ' . $time . ':00';

        $model = new FutureModel();
        if ($model->createLetter($user_id, $title, $message, $delivery_time, $email, $mood)) {
            echo json_encode(['status'=>'success', 'message'=>'Letter sealed successfully!']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Failed to seal letter']);
        }
        exit;
    }

    // API: Lấy kỷ niệm theo Mood
    public function getMoodEchoes() {
        header('Content-Type: application/json');
        session_start();
        $user_id = $_SESSION['user_id'] ?? 0;
        
        $mood = $_GET['mood'] ?? '';

        $model = new FutureModel();
        $memories = $model->getMemoriesByMood($user_id, $mood);

        echo json_encode(['status'=>'success', 'data'=>$memories]);
        exit;
    }
    
    // Hiển thị trang View
    public function index() {
        session_start();
        // ... Load head, topbar ...
        include __DIR__ . '/../views/layouts/head.php';
        echo '<link rel="stylesheet" href="assets/css/future.css">'; // Nhớ tạo file css này
        include __DIR__ . '/../views/layouts/topbar.php';
        
        include __DIR__ . '/../views/future_view.php';
        
        // Load JS
        echo '<script src="https://unpkg.com/phosphor-icons"></script>';
        echo '<script src="assets/js/future.js"></script>';
        
        include __DIR__ . '/../views/layouts/footer.php';
    }
}
?>