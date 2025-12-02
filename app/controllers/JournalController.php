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

        // --- 3. LOGIC MỚI: KIỂM TRA ẢNH PREVIEW VISION BOARD ---
        
        // Đường dẫn file hệ thống (để kiểm tra file_exists)
        // __DIR__ là thư mục app/controllers, cần đi lùi 2 cấp (../../) để ra root
        $previewDirRelative = "/../../assets/uploads/vision_previews/";
        $previewPathSystem = __DIR__ . $previewDirRelative . "vision_user_" . $user_id . ".png";
        
        // Đường dẫn URL (để hiển thị trên thẻ img src)
        $visionPreviewSrc = "assets/uploads/vision_previews/vision_user_" . $user_id . ".png";
        
        // Kiểm tra xem file có thật sự tồn tại không
        if (file_exists($previewPathSystem)) {
            // Thêm tham số time() để tránh cache (giúp ảnh cập nhật ngay khi vừa Save bên kia)
            $visionPreviewSrc .= "?v=" . time();
        } else {
            // Nếu chưa có ảnh thì gán null
            $visionPreviewSrc = null; 
        }
        // -------------------------------------------------------

        // 4. Render View
        include __DIR__ . '/../views/layouts/head.php';
        echo '<link rel="stylesheet" href="assets/css/journal.css">';
        include __DIR__ . '/../views/layouts/topbar.php';

        // Biến $visionPreviewSrc sẽ được dùng bên trong journal_view.php
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
        $title = $_POST['title'] ?? '';
        
        // 2. Nhận TÊN topic từ input text (không phải ID)
        $topic_name = $_POST['topic_name'] ?? ''; 

        if (trim($title) === '') {
            echo json_encode(['status' => 'error', 'message' => 'Title required']);
            return;
        }

        $model = new JournalModel();

        // 3. LOGIC QUAN TRỌNG:
        // Từ cái tên topic người dùng nhập -> Tìm ID cũ hoặc Tạo mới lấy ID
        $topic_id = $model->getOrCreateTopic($user_id, $topic_name);

        // 4. Gọi hàm Model để lưu Goal với cái ID vừa tìm được
        $model->addGoal($user_id, $title, $topic_id);

        echo json_encode(['status' => 'success']);
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

        // 1. Tạo Log mới (Vẫn lưu số % của bài viết này bình thường)
        $created = $model->createLog($user_id, $goal_id, $mood, $content, $progress, $imagePath);

        if ($created) {
            // 2. [SỬA ĐOẠN NÀY]: Gọi hàm tính lại Max Progress
            $newMaxProgress = $model->updateGoalProgressToMax($goal_id);

            // 3. Trả về success CỘNG VỚI số % mới nhất để JS cập nhật giao diện
            echo json_encode([
                'status' => 'success',
                'new_progress' => $newMaxProgress // Gửi số này về cho JS
            ]);
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
}
