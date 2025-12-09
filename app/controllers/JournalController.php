<?php
// app/controllers/JournalController.php
require_once __DIR__ . '/../models/JournalModel.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';

class JournalController
{
    public function show()
    {
        if (session_status() == PHP_SESSION_NONE) session_start();

        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        $user_id = $_SESSION['user_id'];

        $model = new JournalModel();
        
        // 1. Lấy dữ liệu Goals & Logs
        $topics = $model->getTopics($user_id);
        $goals = $model->getGoalsByUser($user_id);
        $logs = $model->getLogsByUserId($user_id);

        // 2. Lấy thông tin Profile & Stats
        $profile = $this->getUserProfile($user_id);
        $activityStats = $model->getLast7DaysStats($user_id);

        // --- [MỚI] 3. LẤY DANH SÁCH DAILY HABITS ---
        // Biến $dailyHabits này sẽ được dùng trong journal_view.php để vẽ "Daily Garden"
        $dailyHabits = $model->getDailyHabits($user_id);
        // -------------------------------------------

        // 4. KIỂM TRA ẢNH PREVIEW VISION BOARD
        $previewDirRelative = "/../../assets/uploads/vision_previews/";
        $previewPathSystem = __DIR__ . $previewDirRelative . "vision_user_" . $user_id . ".png";
        
        $visionPreviewSrc = "assets/uploads/vision_previews/vision_user_" . $user_id . ".png";
        
        if (file_exists($previewPathSystem)) {
            $visionPreviewSrc .= "?v=" . time();
        } else {
            $visionPreviewSrc = null; 
        }

        // 5. Render View
        include __DIR__ . '/../views/layouts/head.php';
        echo '<link rel="stylesheet" href="assets/css/journal.css">';
        include __DIR__ . '/../views/layouts/topbar.php';

        // Các biến $dailyHabits, $goals... sẽ tự động có mặt bên trong file này
        include __DIR__ . '/../views/journal_view.php';
        
        echo '<script src="assets/js/journal.js"></script>';
        include __DIR__ . '/../views/layouts/footer.php';
    }

    private function getUserProfile($user_id)
    {
        // lấy profile từ DB (đơn giản, bạn có thể tách thành model riêng)
        $db = new Database();
        $conn = $db->connect();
        $stmt = $conn->prepare("SELECT user_id, username, email, avatar, created_at FROM users WHERE user_id = :uid LIMIT 1");
        $stmt->bindParam(':uid', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // File: app/controllers/JournalController.php

    public function addGoal()
    {
        // 1. Kiểm tra đăng nhập
        if (session_status() == PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        $user_id = $_SESSION['user_id'];
        
        // Lấy dữ liệu cơ bản
        $title = $_POST['title'] ?? '';
        $topic_name = $_POST['topic_name'] ?? ''; 

        // [MỚI] Lấy thêm dữ liệu Thói quen và Ngày tháng từ form
        $daily_habit = $_POST['daily_habit'] ?? '';
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;

        if (trim($title) === '') {
            echo json_encode(['status' => 'error', 'message' => 'Title required']);
            return;
        }

        $model = new JournalModel();

        // 3. Xử lý Topic
        $topic_id = $model->getOrCreateTopic($user_id, $topic_name);

        // 4. [CẬP NHẬT] Gọi hàm addGoal với đầy đủ tham số mới
        // Thứ tự tham số phải khớp với bên Model: ($user_id, $title, $topic_id, $habit_title, $start_date, $end_date)
        $result = $model->addGoal($user_id, $title, $topic_id, $daily_habit, $start_date, $end_date);

        if ($result) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database Error']);
        }
    }
    public function getGoalLogs()
    {
        // 1. Đặt header là JSON để trả về dữ liệu đúng định dạng
        header('Content-Type: application/json');

        // 2. Kiểm tra Session
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        if (empty($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        // 3. Lấy dữ liệu đầu vào
        $user_id = $_SESSION['user_id'];
        $goal_id = $_GET['goal_id'] ?? 0;

        if (!$goal_id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing Goal ID']);
            exit;
        }

        // 4. Gọi Model để lấy dữ liệu
        $model = new JournalModel();

        // Gọi hàm lấy logs theo goal_id (Đảm bảo bên Model đã có hàm này)
        $logs = $model->getLogsByGoalId($user_id, $goal_id);

        // 5. Trả kết quả về cho JavaScript
        echo json_encode(['status' => 'success', 'data' => $logs]);
        exit;
    }
    public function addJourney()
    {
        header('Content-Type: application/json');
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        if (empty($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        // Lấy dữ liệu từ Form
        $user_id = $_SESSION['user_id'];
        $goal_id = $_POST['goal_id'];
        $title   = $_POST['journey_title'] ?? '';
        $content = $_POST['content'] ?? '';
        $progress = $_POST['progress'] ?? 0;

        // Xử lý Mood (Mảng checkbox -> chuỗi cách nhau dấu phẩy)
        $mood = isset($_POST['mood']) ? implode(', ', $_POST['mood']) : '';

        // Xử lý Upload Ảnh (Nếu có)
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../assets/uploads/"; // Thư mục lưu ảnh
            if (!file_exists($target_dir))
                mkdir($target_dir, 0777, true);

            // Tạo tên file độc nhất để tránh trùng
            $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Lưu đường dẫn vào DB (bỏ ../ đi để hiển thị trên web cho đúng)
                $imagePath = "assets/uploads/" . $new_filename;
            }
        }

        $model = new JournalModel();

        // 1. Tạo Log mới
        $created = $model->createLog($user_id, $goal_id, $mood, $title, $content, $progress, $imagePath);

        if ($created) {
            // 2. Cập nhật tiến độ Goal (Max Progress)
            $newMaxProgress = $model->updateGoalProgressToMax($goal_id);

            // --- [PHẦN MỚI] CHECK XEM CÓ THƯ TƯƠNG LAI NÀO ĐANG CHỜ KHÔNG ---
            // Gọi hàm tìm thư dựa trên mood vừa nhập (Hàm này bạn đã thêm vào JournalModel ở bước trước)
            $foundLetter = $model->findPendingLetterByMood($user_id, $mood);
            
            // Chuẩn bị dữ liệu trả về
            $response = [
                'status' => 'success',
                'new_progress' => $newMaxProgress
            ];

            // Nếu tìm thấy thư
            if ($foundLetter) {
                // Đánh dấu thư đã mở ngay lập tức (để lần sau không hiện lại)
                // Lưu ý: Kiểm tra cột ID trong database của bạn là 'id' hay 'letter_id' nhé. 
                // Ở đây mình giả định là 'id'.
                $letterId = $foundLetter['id'] ?? $foundLetter['letter_id']; 
               // $model->markLetterAsOpened($letterId); 
                
                // Đính kèm nội dung thư vào JSON để JS hiển thị Popup
                $response['letter_data'] = [
                    'id' => $letterId,
                    'created_at' => date('F j, Y', strtotime($foundLetter['created_at'])), // Format ngày đẹp: Nov 29, 2023
                    'mood' => $foundLetter['mood'],
                    'message' => $foundLetter['message'], // Nội dung thư
                    'title' => $foundLetter['title'] ?? 'A Message from Your Past Self'
                ];
            }
            // ----------------------------------------------------

            echo json_encode($response);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database Error']);
        }
        exit;
    }
    // Thêm vào trong class JournalController
    public function updateJourney()
    {
        header('Content-Type: application/json');
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        if (empty($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        // Lấy và kiểm tra dữ liệu
        $log_id = $_POST['log_id'] ?? 0;
        $goal_id = $_POST['goal_id'] ?? 0;
        $content = $_POST['content'] ?? '';
        $mood = $_POST['mood'] ?? '';
        $progress = $_POST['progress'] ?? 0;
        $user_id = $_SESSION['user_id'];

        if (!$log_id || !$goal_id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
            exit;
        }

        $model = new JournalModel();

        // 1. Cập nhật log
        if ($model->updateLog($log_id, $user_id, $mood, $content, $progress)) {
            // 2. Tính lại % Max
            $newMax = $model->updateGoalProgressToMax($goal_id);

            echo json_encode([
                'status' => 'success',
                'new_progress' => $newMax
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update']);
        }
        exit;
    }
    public function uploadAvatar() {
        // Tắt lỗi HTML để trả về JSON sạch
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        header('Content-Type: application/json');

        try {
            if (session_status() == PHP_SESSION_NONE) session_start();
            
            if (empty($_SESSION['user_id'])) {
                throw new Exception('Unauthorized');
            }

            // 1. Kiểm tra file
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File upload error');
            }

            // 2. Xử lý lưu file
            $target_dir = __DIR__ . "/../../assets/uploads/avatars/"; // Đường dẫn tuyệt đối
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_ext = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($file_ext, $allowed)) {
                throw new Exception('Invalid file type');
            }

            $new_name = "user_" . $_SESSION['user_id'] . "_" . time() . "." . $file_ext;
            $target_file = $target_dir . $new_name;

            if (!move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                throw new Exception('Failed to move file');
            }

            // 3. Cập nhật Database (Gọi UserModel)
            // Đường dẫn lưu vào DB (tương đối từ thư mục gốc)
            $db_path = "assets/uploads/avatars/" . $new_name;
            
            // Load UserModel
            require_once __DIR__ . '/../models/UserModel.php';
            $userModel = new UserModel();
            
            if ($userModel->updateAvatar($_SESSION['user_id'], $db_path)) {
                // Cập nhật session
                $_SESSION['avatar'] = $db_path;
                echo json_encode(['status' => 'success', 'path' => $db_path]);
            } else {
                throw new Exception('Database update failed');
            }

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
    // Thêm hàm này vào trong class JournalController
    public function deleteGoal()
    {
        // Trả về JSON
        header('Content-Type: application/json');
        
        if (session_status() == PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $goal_id = $_POST['goal_id'] ?? 0;

        if (!$goal_id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing Goal ID']);
            exit;
        }

        $model = new JournalModel();
        
        // Gọi hàm xóa trong Model
        if ($model->deleteGoal($goal_id, $user_id)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Could not delete goal']);
        }
        exit;
    }
}
