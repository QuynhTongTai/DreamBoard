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

    public function createLog($user_id, $goal_id, $mood, $title, $content, $image = null)
    {
        // Bỏ progress_update khỏi câu SQL
        $query = "INSERT INTO " . $this->table_name . " 
              (user_id, goal_id, mood, journey_title, content, image, created_at) 
              VALUES (:user_id, :goal_id, :mood, :title, :content, :image, NOW())";

        $stmt = $this->conn->prepare($query);

        $content = htmlspecialchars(strip_tags($content));
        $mood = htmlspecialchars(strip_tags($mood));
        $title = htmlspecialchars(strip_tags($title));

        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':goal_id', $goal_id);
        $stmt->bindParam(':mood', $mood);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        // $stmt->bindParam(':progress', $progress); // Xóa dòng này
        $stmt->bindParam(':image', $image);

        return $stmt->execute();
    }

    // 2.Hàm LẤY TẤT CẢ NHẬT KÝ CỦA 1 USER (Read)
    // app/models/JournalModel.php

    // 1. Sửa hàm getLogsByUserId để nhận thêm limit và offset
    public function getLogsByUserId($user_id, $limit = 10, $offset = 0)
    {
        // Thêm LIMIT và OFFSET vào câu SQL
        $query = "SELECT j.*, g.title as goal_title, g.topic_id 
              FROM " . $this->table_name . " j
              LEFT JOIN goals g ON j.goal_id = g.goal_id
              WHERE j.user_id = :user_id 
              ORDER BY j.created_at DESC
              LIMIT :limit OFFSET :offset"; // [MỚI]

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);

        // [QUAN TRỌNG] Phải bind dạng INT, nếu không MySQL sẽ lỗi
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Thêm hàm đếm tổng số bài (để biết có bao nhiêu trang)
    public function countLogsByUserId($user_id)
    {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchColumn();
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

    public function updateLog($log_id, $user_id, $mood, $content)
    {
        // Bỏ progress_update khỏi câu SQL
        $query = "UPDATE " . $this->table_name . "
                  SET mood = :mood, content = :content
                  WHERE log_id = :log_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mood', $mood);
        $stmt->bindParam(':content', $content);
        // $stmt->bindParam(':progress', $progress); // Xóa dòng này
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
    // File: app/models/JournalModel.php

    // Sửa lại hàm addGoal để nhận thêm habit, start_date, end_date
    // File: app/models/JournalModel.php

    // [CẬP NHẬT] Tham số $habit_titles bây giờ nhận vào là Mảng (array)
    public function addGoal($user_id, $title, $topic_id, $habit_titles = [], $start_date = null, $end_date = null)
    {
        // 1. INSERT GOAL (Giữ nguyên)
        $query = "INSERT INTO goals (user_id, title, topic_id, progress, start_date, end_date, created_at) 
                  VALUES (:uid, :title, :topic_id, 0, :start, :end, NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $user_id);
        $stmt->bindParam(':title', $title);

        if (empty($topic_id) || $topic_id === 'all')
            $stmt->bindValue(':topic_id', null, PDO::PARAM_NULL);
        else
            $stmt->bindParam(':topic_id', $topic_id);

        $stmt->bindValue(':start', !empty($start_date) ? $start_date : null);
        $stmt->bindValue(':end', !empty($end_date) ? $end_date : null);

        if ($stmt->execute()) {
            $goal_id = $this->conn->lastInsertId();

            // 2. [CẬP NHẬT] Vòng lặp INSERT HABITS
            // Kiểm tra nếu có danh sách thói quen thì mới lưu
            if (!empty($habit_titles) && is_array($habit_titles)) {
                $sqlHabit = "INSERT INTO habits (goal_id, user_id, title, created_at) 
                             VALUES (:gid, :uid, :title, NOW())";
                $stmtH = $this->conn->prepare($sqlHabit);

                foreach ($habit_titles as $habit_name) {
                    $habit_name = trim($habit_name);
                    // Chỉ lưu nếu tên thói quen không bị rỗng
                    if (!empty($habit_name)) {
                        $stmtH->execute([
                            ':gid' => $goal_id,
                            ':uid' => $user_id,
                            ':title' => $habit_name
                        ]);
                    }
                }
            }

            return $goal_id;
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
    // ... (Các hàm cũ giữ nguyên) ...

    // [MỚI] Lưu kết quả AI vào bài nhật ký
    public function saveAiReflection($log_id, $analysis, $advice, $quote)
    {
        $query = "UPDATE journey_log 
                  SET ai_analysis = :analysis, 
                      ai_advice = :advice, 
                      ai_quote = :quote 
                  WHERE log_id = :log_id";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':analysis' => $analysis,
            ':advice' => $advice,
            ':quote' => $quote,
            ':log_id' => $log_id
        ]);
    }
    // --- CÁC HÀM DAILY GARDEN (Thêm vào cuối file) ---

    // 1. Lấy danh sách Habit của User + Trạng thái hôm nay
    public function getDailyHabits($user_id)
    {
        $today = date('Y-m-d');
        // Join với bảng logs để xem hôm nay (check_date = $today) đã có dữ liệu chưa
        // Nếu log_id khác null tức là đã làm (is_done = 1)
        $query = "SELECT h.*, g.title as goal_title, hl.log_id as is_done
                  FROM habits h
                  JOIN goals g ON h.goal_id = g.goal_id
                  LEFT JOIN habit_logs hl ON h.habit_id = hl.habit_id AND hl.check_date = :today
                  WHERE h.user_id = :uid 
                  ORDER BY h.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':uid' => $user_id, ':today' => $today]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Check/Uncheck Habit & Tăng Progress
    public function toggleHabit($habit_id, $date)
    {
        // A. Kiểm tra xem đã làm chưa
        $check = $this->conn->prepare("SELECT log_id FROM habit_logs WHERE habit_id = ? AND check_date = ?");
        $check->execute([$habit_id, $date]);
        $exists = $check->fetch();

        // Lấy goal_id để update progress
        $getGoal = $this->conn->prepare("SELECT goal_id FROM habits WHERE habit_id = ?");
        $getGoal->execute([$habit_id]);
        $goal_id = $getGoal->fetchColumn();

        if ($exists) {
            // B1. Đã làm -> Hủy (Uncheck) -> Xóa log
            $del = $this->conn->prepare("DELETE FROM habit_logs WHERE log_id = ?");
            $del->execute([$exists['log_id']]);

            // Giảm progress đi 1 chút (ví dụ 2%)
            if ($goal_id) {
                $this->conn->prepare("UPDATE goals SET progress = GREATEST(0, progress - 2) WHERE goal_id = ?")->execute([$goal_id]);
            }
            return 'unchecked';
        } else {
            // B2. Chưa làm -> Check -> Thêm log
            $ins = $this->conn->prepare("INSERT INTO habit_logs (habit_id, check_date) VALUES (?, ?)");
            $ins->execute([$habit_id, $date]);

            // Tăng progress lên 1 chút (ví dụ 2%) - Đây là phần "Tích tiểu thành đại"
            if ($goal_id) {
                $this->conn->prepare("UPDATE goals SET progress = LEAST(100, progress + 2) WHERE goal_id = ?")->execute([$goal_id]);
            }
            return 'checked';
        }
    }
    // Cập nhật Progress dựa trên Habit & Thời gian
    public function calculateGoalProgress($goal_id)
    {
        // 1. Lấy thông tin Goal (Start Date, End Date)
        $stmtGoal = $this->conn->prepare("SELECT start_date, end_date FROM goals WHERE goal_id = :gid");
        $stmtGoal->execute([':gid' => $goal_id]);
        $goal = $stmtGoal->fetch(PDO::FETCH_ASSOC);

        if (!$goal || empty($goal['start_date']) || empty($goal['end_date'])) {
            return 0; // Không đủ dữ liệu ngày tháng để tính
        }

        // 2. Tính tổng số ngày (Duration)
        $start = new DateTime($goal['start_date']);
        $end = new DateTime($goal['end_date']);
        // +1 vì nếu start=end thì vẫn tính là 1 ngày
        $days = $end->diff($start)->days + 1;

        // 3. Đếm số lượng Habit thuộc Goal này
        $stmtCountHabit = $this->conn->prepare("SELECT COUNT(*) FROM habits WHERE goal_id = :gid");
        $stmtCountHabit->execute([':gid' => $goal_id]);
        $totalHabitsPerDay = $stmtCountHabit->fetchColumn();

        if ($totalHabitsPerDay == 0)
            return 0; // Không có habit nào thì progress = 0

        // 4. Tính Mẫu số (Tổng số tick tối đa có thể đạt được trong suốt quá trình)
        $maxPossibleTicks = $days * $totalHabitsPerDay;

        // 5. Tính Tử số (Tổng số tick thực tế đã làm được từ bảng logs)
        // Join bảng habits để chắc chắn chỉ đếm log của goal này
        $queryActual = "SELECT COUNT(*) FROM habit_logs hl 
                    JOIN habits h ON hl.habit_id = h.habit_id 
                    WHERE h.goal_id = :gid";
        $stmtActual = $this->conn->prepare($queryActual);
        $stmtActual->execute([':gid' => $goal_id]);
        $actualTicks = $stmtActual->fetchColumn();

        // 6. Tính phần trăm
        $progress = 0;
        if ($maxPossibleTicks > 0) {
            $progress = ($actualTicks / $maxPossibleTicks) * 100;
        }

        // Làm tròn (lấy số nguyên hoặc 1 số lẻ)
        $progress = round($progress);

        // Giới hạn max 100 (phòng trường hợp tick quá ngày end date)
        if ($progress > 100)
            $progress = 100;

        // 7. Update vào Database
        $update = $this->conn->prepare("UPDATE goals SET progress = :p WHERE goal_id = :gid");
        $update->execute([':p' => $progress, ':gid' => $goal_id]);

        return $progress;
    }
    // 1. Lấy Goal ID từ Habit ID
    public function getGoalIdByHabit($habit_id)
    {
        $stmt = $this->conn->prepare("SELECT goal_id FROM habits WHERE habit_id = :hid LIMIT 1");
        $stmt->execute([':hid' => $habit_id]);
        return $stmt->fetchColumn(); // Trả về goal_id hoặc false
    }
}
?>