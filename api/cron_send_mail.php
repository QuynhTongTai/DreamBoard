<?php
// File: api/cron_send_mail.php

// 1. Load thư viện Composer
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Kết nối Database
require_once __DIR__ . '/../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $db = new Database();
    $conn = $db->connect();

    // 3. Tìm các bức thư ĐẾN HẠN và CHƯA GỬI
    $query = "SELECT * FROM future_letters 
              WHERE is_opened = 0 
              AND open_date IS NOT NULL 
              AND open_date <= NOW() 
              AND recipient_email IS NOT NULL 
              AND recipient_email != ''";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $letters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($letters) > 0) {
        echo "<h3>📮 Tìm thấy " . count($letters) . " bức thư cần gửi:</h3>";

        $mail = new PHPMailer(true);

        // --- CẤU HÌNH SERVER GMAIL ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'qtong4969@gmail.com'; // Gmail của bạn
        $mail->Password   = 'mynd zeco tvoa vzow';    // App Password của bạn
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom('dreamboard47@gmail.com', 'Future Letter Service');

        foreach ($letters as $letter) {
            // [SỬA QUAN TRỌNG]: Lấy đúng tên cột letter_id từ database
            $currentId = $letter['letter_id']; 

            try {
                $mail->clearAddresses();
                $mail->addAddress($letter['recipient_email']);

                $mail->isHTML(true);
                $mail->Subject = "Future Letter: " . ($letter['title'] ?? 'A Message from the Past');
                
                $bodyContent = "
                    <div style='background-color: #f3e8ff; padding: 20px; font-family: sans-serif; text-align: center;'>
                        <div style='background: white; padding: 30px; border-radius: 15px; max-width: 600px; margin: auto; box-shadow: 0 5px 15px rgba(0,0,0,0.1);'>
                            <h2 style='color: #6b5bff; margin-top: 0;'>📬 Delivery from the Past!</h2>
                            <p style='color: #888; font-size: 14px;'>Sealed on: " . date('d/m/Y', strtotime($letter['created_at'])) . "</p>
                            <hr style='border: 0; border-top: 1px dashed #ddd; margin: 25px 0;'>
                            <div style='text-align: left; font-size: 16px; line-height: 1.6; color: #333; white-space: pre-line;'>
                                " . $letter['message'] . "
                            </div>
                            <br>
                            <div style='margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; color: #aaa; font-size: 12px;'>
                                Mood when written: <strong>" . htmlspecialchars($letter['mood']) . "</strong>
                            </div>
                        </div>
                    </div>
                ";
                $mail->Body = $bodyContent;
                $mail->AltBody = strip_tags($letter['message']);

                $mail->send();
                
                // [SỬA]: Dùng $currentId (tức là letter_id) để in ra màn hình
                echo "<p style='color:green'>✅ Đã gửi thư ID: <strong>" . $currentId . "</strong> tới " . $letter['recipient_email'] . "</p>";

                // [SỬA QUAN TRỌNG]: Đổi 'id' thành 'letter_id' trong câu lệnh UPDATE
                $update = $conn->prepare("UPDATE future_letters SET is_opened = 1 WHERE letter_id = :id");
                $update->execute([':id' => $currentId]);

            } catch (Exception $e) {
                // [SỬA]: Dùng $currentId để báo lỗi
                echo "<p style='color:red'>❌ Lỗi gửi thư ID " . $currentId . ": {$mail->ErrorInfo}</p>";
            }
        }
    } else {
        echo "<p style='color:gray'>📭 Không có bức thư nào đến hạn gửi lúc này.</p>";
    }

} catch (Exception $e) {
    echo "<h1>Lỗi Server:</h1> " . $e->getMessage();
}
?>