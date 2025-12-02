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

    public function createLog($user_id, $goal_id, $mood, $content, $progress, $image = null)
    {
        //:user_id, :goal_id, ...: Đây là các Chỗ giữ chỗ có tên (Named Placeholders). Chúng đóng vai trò là "biến" an toàn trong câu lệnh SQL. Đây là cơ chế chính của Prepared Statements chống SQL Injection.
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, goal_id, mood, content, progress_update, image) 
                  VALUES (:user_id, :goal_id, :mood, :content, :progress, :image)";
        $stmt = $this->conn->prepare($query);
        //Làm sạch dữ liệu ($content, $mood)
        $content = htmlspecialchars(strip_tags($content));
        $mood = htmlspecialchars(strip_tags($mood));
        //Phương thức này gắn giá trị của biến PHP vào chỗ giữ chỗ tương ứng trong câu lệnh SQL.
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':goal_id', $goal_id);
        $stmt->bindParam(':mood', $mood);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':progress', $progress);
        $stmt->bindParam(':image', $image);
        return $stmt->execute();
    }

    // 2.Hàm LẤY TẤT CẢ NHẬT KÝ CỦA 1 USER (Read)
    public function getLogsByUserId($user_id) {
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
        $query = "SELECT * FROM goals WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // --- HÀM MỚI: TÌM HOẶC TẠO TOPIC ---
    public function getOrCreateTopic($user_id, $topic_name) {
        $topic_name = trim($topic_name);
        if (empty($topic_name)) return null;

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
        $colors = ['#FFD8C3', '#C6A7FF', '#B7E3D0', '#FFCCA7', '#ACE7FF', '#FFABAB'];
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
    public function addGoal($user_id, $title, $topic_id) {
        $query = "INSERT INTO goals (user_id, title, topic_id, progress, created_at) 
                  VALUES (:uid, :title, :topic_id, 0, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':uid', $user_id);
        $stmt->bindParam(':title', $title);
        
        // Xử lý nếu topic_id là null (topic chung)
        if (empty($topic_id) || $topic_id === 'all') {
            $stmt->bindValue(':topic_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':topic_id', $topic_id);
        }
        
        return $stmt->execute();
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
public function getTopics($user_id) {
        $query = "SELECT * FROM topics WHERE user_id = :uid";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':uid' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Thêm vào JournalModel
    public function getLast7DaysStats($user_id) {
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
}
?>