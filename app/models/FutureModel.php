<?php
require_once __DIR__ . '/../../config/database.php';

class FutureModel {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // 1. Lưu thư gửi tương lai
    public function createLetter($user_id, $title, $message, $open_date, $email, $mood_tag) {
        $query = "INSERT INTO future_letters 
                  (user_id, title, message, open_date, mood, is_opened, created_at) 
                  VALUES (:uid, :title, :msg, :odate, :mood, 0, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':uid' => $user_id,
            ':title' => $title,
            ':msg' => $message,
            ':odate' => $open_date,
            ':mood' => $mood_tag
        ]);
        // Lưu ý: Mình chưa lưu email vào bảng future_letters trong DB cũ của bạn. 
        // Nếu muốn gửi mail thật, bạn cần thêm cột 'recipient_email' vào bảng này nhé.
        return true;
    }

    // 2. Lấy các bài viết (Journey Logs) theo cảm xúc
    // Dùng để hiển thị bên phần "Echoes of Moods Past"
    public function getMemoriesByMood($user_id, $mood) {
        $query = "SELECT content, created_at, mood, image 
                  FROM journey_log 
                  WHERE user_id = :uid AND mood LIKE :mood 
                  ORDER BY created_at DESC LIMIT 5";
        
        $stmt = $this->conn->prepare($query);
        // Dùng % để tìm kiếm tương đối (ví dụ mood="very happy" vẫn tìm ra "happy")
        $stmt->execute([
            ':uid' => $user_id,
            ':mood' => '%' . $mood . '%'
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>