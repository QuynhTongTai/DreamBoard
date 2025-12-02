<?php
require_once __DIR__ . '/../../config/database.php';

class VisionBoardModel {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getOrCreateBoard($user_id) {
        $query = "SELECT board_id FROM vision_board WHERE user_id = :uid LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':uid' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) return $row['board_id'];

        $insert = "INSERT INTO vision_board (user_id, title) VALUES (:uid, 'My 2025 Vision')";
        $stmtIn = $this->conn->prepare($insert);
        $stmtIn->execute([':uid' => $user_id]);
        return $this->conn->lastInsertId();
    }

    public function updateTimestamp($board_id) {
        $sql = "UPDATE vision_board SET updated_at = NOW() WHERE board_id = :bid";
        $this->conn->prepare($sql)->execute([':bid' => $board_id]);
    }
}
?>