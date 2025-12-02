<?php
require_once __DIR__ . '/../models/VisionBoardModel.php';
require_once __DIR__ . '/../models/VisionItemModel.php';

class VisionController {
    
    public function index() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
        
        include __DIR__ . '/../views/layouts/head.php';
        echo '<link rel="stylesheet" href="assets/css/vision.css">';
        include __DIR__ . '/../views/layouts/topbar.php';
        include __DIR__ . '/../views/vision_view.php';
        
        echo '<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>';
        echo '<script src="assets/js/vision.js"></script>';
        
        include __DIR__ . '/../views/layouts/footer.php';
    }

    // API Load dữ liệu
    public function getBoardData() {
        header('Content-Type: application/json');
        if (session_status() == PHP_SESSION_NONE) session_start();
        $user_id = $_SESSION['user_id'] ?? 0;

        $boardModel = new VisionBoardModel();
        $itemModel = new VisionItemModel();

        $board_id = $boardModel->getOrCreateBoard($user_id);
        $items = $itemModel->getItems($board_id);

        echo json_encode(['status' => 'success', 'items' => $items]);
        exit;
    }

    // API Lưu dữ liệu
    public function saveBoardData() {
        header('Content-Type: application/json');
        if (session_status() == PHP_SESSION_NONE) session_start();
        $user_id = $_SESSION['user_id'] ?? 0;
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // SỬA Ở ĐÂY: Chỉ kiểm tra user_id và sự tồn tại của key 'items'
        // Cho phép $data['items'] là mảng rỗng []
        if (!$user_id || !isset($data['items'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']); 
            exit;
        }

        $boardModel = new VisionBoardModel();
        $itemModel = new VisionItemModel();

        try {
            $board_id = $boardModel->getOrCreateBoard($user_id);
            
            // Xóa cũ -> Thêm mới
            $itemModel->clearBoard($board_id);
            foreach ($data['items'] as $item) {
                $itemModel->addItem($board_id, $item);
            }
            $boardModel->updateTimestamp($board_id);

            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // API Upload ảnh riêng cho Vision
    public function uploadImage() {
        header('Content-Type: application/json');
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = __DIR__ . "/../../assets/uploads/vision/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

            $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $filename = uniqid() . "." . $ext;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $filename)) {
                echo json_encode(['status' => 'success', 'path' => "assets/uploads/vision/" . $filename]);
            } else {
                echo json_encode(['status' => 'error']);
            }
        }
        exit;
    }
}
?>