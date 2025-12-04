<?php
require_once __DIR__ . '/../../config/database.php';


class JournalModel
{
    private $conn;
    private $table_name = "journey_log";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function createLog($user_id, $goal_id, $mood, $title, $content, $progress, $image = null)
    {
        $query = "INSERT INTO " . $this->table_name . " 
              (user_id, goal_id, mood, journey_title, content, progress_update, image, created_at) 
              VALUES (:user_id, :goal_id, :mood, :title, :content, :progress, :image, NOW())";

        $stmt = $this->conn->prepare($query);

        // Làm sạch data
        $content = htmlspecialchars(strip_tags($content));
        $mood = htmlspecialchars(strip_tags($mood));
        $title = htmlspecialchars(strip_tags($title)); // Mới

        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':goal_id', $goal_id);
        $stmt->bindParam(':mood', $mood);
        $stmt->bindParam(':title', $title); // Mới
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':progress', $progress);
        $stmt->bindParam(':image', $image);

        return $stmt->execute();
    }

    // 2.Hàm LẤY TẤT CẢ NHẬT KÝ CỦA 1 USER (Read)
    public function getLogsByUserId($user_id)
    {
        $query = "SELECT j.*, g.title as goal_title, g.topic_id 
                  FROM " . $this->table_name . " j
                  LEFT JOIN goals g ON j.goal_id = g.goal_id
                  WHERE j.user_id = :user_id 
                  ORDER BY j.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // 3. Hàm LẤY CHI TIẾT 1 BÀI NHẬT KÝ (Để sửa hoặc xem riêng)
    public function getLogById($log_id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE log_id = :log_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':log_id', $log_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteLog($log_id, $user_id)
    {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE log_id = :log_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':log_id', $log_id);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }

    public function updateLog($log_id, $user_id, $mood, $content, $progress)
    {
        $query = "UPDATE " . $this->table_name . "
                  SET mood = :mood, content = :content, progress_update = :progress
                  WHERE log_id = :log_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mood', $mood);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':progress', $progress);
        $stmt->bindParam(':log_id', $log_id);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }

    // NEW: lấy goals của user
    public function getGoalsByUser($user_id)
    {
        // JOIN với bảng topics để lấy màu (t.color) và tên topic (t.name)
        $query = "SELECT g.*, t.color as topic_color, t.name as topic_name 
                  FROM goals g
                  LEFT JOIN topics t ON g.topic_id = t.topic_id
                  WHERE g.user_id = :user_id 
                  ORDER BY g.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // --- HÀM MỚI: TÌM HOẶC TẠO TOPIC ---
    public function getOrCreateTopic($user_id, $topic_name)
    {
        $topic_name = trim($topic_name);
        if (empty($topic_name))
            return null;

        // 1. Kiểm tra xem topic đã tồn tại chưa
        $queryCheck = "SELECT topic_id FROM topics WHERE user_id = :uid AND name = :name LIMIT 1";
        $stmt = $this->conn->prepare($queryCheck);
        $stmt->execute([':uid' => $user_id, ':name' => $topic_name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row['topic_id']; // Đã có -> Trả về ID cũ
        }

        // 2. Nếu chưa có -> Tạo mới
        // Random màu pastel xinh xinh cho topic mới
        $colors = [
            '#FFF0F5',
            '#F0F8FF',
            '#F5F5DC',
            '#E6E6FA',
            '#F0FFF4',
            '#FFFACD',
            '#F3E8FF',
            '#FFE4E1'
        ];
        $randomColor = $colors[array_rand($colors)];

        $queryInsert = "INSERT INTO topics (user_id, name, color) VALUES (:uid, :name, :color)";
        $stmtIn = $this->conn->prepare($queryInsert);
        $stmtIn->execute([
            ':uid' => $user_id,
            ':name' => $topic_name,
            ':color' => $randomColor
        ]);

        return $this->conn->lastInsertId(); // Trả về ID vừa tạo
    }
    // File: app/models/JournalModel.php

    // Hàm này chỉ làm nhiệm vụ lưu vào Database
    public function addGoal($user_id, $title, $topic_id)
    {
        $query = "INSERT INTO goals (user_id, title, topic_id, progress, created_at) 
              VALUES (:uid, :title, :topic_id, 0, NOW())";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':uid', $user_id);
        $stmt->bindParam(':title', $title);

        if (empty($topic_id) || $topic_id === 'all') {
            $stmt->bindValue(':topic_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':topic_id', $topic_id);
        }

        if ($stmt->execute()) {
            // [QUAN TRỌNG] Trả về ID của goal vừa tạo
            return $this->conn->lastInsertId();
        }
        return false;
    }
    // Thêm hàm này vào trong class JournalModel
    public function deleteGoal($goal_id, $user_id)
    {
        try {
            // BƯỚC 1: Xóa tất cả Nhật ký (Logs) liên quan đến Goal này trước
            $queryLogs = "DELETE FROM journey_log WHERE goal_id = :goal_id AND user_id = :user_id";
            $stmtLogs = $this->conn->prepare($queryLogs);
            $stmtLogs->execute([':goal_id' => $goal_id, ':user_id' => $user_id]);

            // BƯỚC 2: Sau đó mới xóa Goal
            $queryGoal = "DELETE FROM goals WHERE goal_id = :goal_id AND user_id = :user_id";
            $stmtGoal = $this->conn->prepare($queryGoal);
            $stmtGoal->execute([':goal_id' => $goal_id, ':user_id' => $user_id]);

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    // Thêm vào class JournalModel
    public function getLogsByGoalId($user_id, $goal_id)
    {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND goal_id = :goal_id 
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':goal_id', $goal_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // --- XÓA HÀM updateGoalProgress CŨ VÀ DÁN ĐÈ HÀM NÀY VÀO ---

    // Hàm này tự động tính % cao nhất (Max) từ lịch sử và cập nhật vào Goal
    public function updateGoalProgressToMax($goal_id)
    {

        // 1. Lấy giá trị max
        $queryMax = "SELECT MAX(progress_update) AS max_progress
                 FROM {$this->table_name}
                 WHERE goal_id = :goal_id";

        $stmt = $this->conn->prepare($queryMax);
        $stmt->bindParam(':goal_id', $goal_id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Nếu không có bản ghi update
        if ($result['max_progress'] === null) {
            return false;
        }

        // Convert int + giới hạn 0–100
        $max_progress = max(0, min(100, (int) $result['max_progress']));

        // 2. Update bảng goals
        $queryUpdate = "UPDATE goals 
                    SET progress = :progress 
                    WHERE goal_id = :goal_id";

        $stmtUpdate = $this->conn->prepare($queryUpdate);
        $stmtUpdate->bindParam(':progress', $max_progress, PDO::PARAM_INT);
        $stmtUpdate->bindParam(':goal_id', $goal_id, PDO::PARAM_INT);

        if ($stmtUpdate->execute()) {
            return $max_progress;
        }

        return false;
    }
    public function getTopics($user_id)
    {
        $query = "SELECT * FROM topics WHERE user_id = :uid";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':uid' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Thêm vào JournalModel
    public function getLast7DaysStats($user_id)
    {
        // Lấy ngày và số lượng bài viết trong 7 ngày gần nhất
        $query = "SELECT DATE(created_at) as entry_date, COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  WHERE user_id = :uid 
                  AND created_at >= DATE(NOW()) - INTERVAL 6 DAY
                  GROUP BY entry_date";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':uid' => $user_id]);

        // Chuyển kết quả thành mảng dạng ['2025-11-28' => 2, '2025-11-29' => 1]
        $stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['entry_date']] = $row['count'];
        }
        return $stats;
    }
    // File: app/models/JournalModel.php

    // 1. Tìm thư đang chờ theo Mood
// Trong file app/models/JournalModel.php

    public function findPendingLetterByMood($user_id, $mood_list_string)
    {
        // 1. Kiểm tra đầu vào kỹ hơn
        if (empty($mood_list_string))
            return null;

        $moods = array_map('trim', explode(',', $mood_list_string));
        // Loại bỏ các phần tử rỗng (để tránh lỗi LIKE '%%' tìm ra tất cả)
        $moods = array_filter($moods);

        if (empty($moods))
            return null;

        $conditions = [];
        $params = [':uid' => $user_id];

        foreach ($moods as $index => $m) {
            $key = ":mood_$index";
            // Dùng % để tìm kiếm linh hoạt (vd: 'Happy' tìm được trong 'Very Happy')
            $conditions[] = "mood LIKE $key";
            $params[$key] = '%' . $m . '%';
        }

        $sqlCondition = implode(' OR ', $conditions);

        // 2. Thêm try-catch để nếu lỗi SQL cũng không làm sập web (Error 500)
        try {
            // Lưu ý: Đảm bảo bảng future_letters có cột is_opened và open_date
            $query = "SELECT * FROM future_letters 
                  WHERE user_id = :uid 
                  AND is_opened = 0 
                   
                  AND ($sqlCondition) 
                  ORDER BY RAND()
                  LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Ghi log lỗi nếu cần, trả về null để code vẫn chạy tiếp
            error_log("Database Error in findPendingLetterByMood: " . $e->getMessage());
            return null;
        }
    }

    // 2. Đánh dấu thư đã mở
    public function markLetterAsOpened($letter_id)
    {
        $query = "UPDATE future_letters SET is_opened = 1, open_date = NOW() WHERE letter_id = :id";
        // Lưu ý: check lại tên cột ID trong bảng future_letters của bạn là 'id' hay 'letter_id'
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $letter_id]);
    }
}
?>