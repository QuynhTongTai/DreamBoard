<?php
require_once __DIR__ . '/../../config/database.php';

class VisionItemModel {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getItems($board_id) {
        $query = "SELECT * FROM vision_items WHERE board_id = :bid ORDER BY z_index ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':bid' => $board_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clearBoard($board_id) {
        $query = "DELETE FROM vision_items WHERE board_id = :bid";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':bid' => $board_id]);
    }

    public function addItem($board_id, $item) {
        $query = "INSERT INTO vision_items 
                  (board_id, type, content, image_path, pos_x, pos_y, width, height, rotation, z_index) 
                  VALUES (:bid, :type, :content, :img, :x, :y, :w, :h, :rot, :z)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':bid' => $board_id,
            ':type' => $item['type'],
            ':content' => $item['content'] ?? '',
            ':img' => $item['image_path'] ?? '',
            ':x' => $item['pos_x'],
            ':y' => $item['pos_y'],
            ':w' => $item['width'] ?? 200,
            ':h' => $item['height'] ?? 200,
            ':rot' => $item['rotation'] ?? 0,
            ':z' => $item['z_index'] ?? 1
        ]);
    }
}
?>