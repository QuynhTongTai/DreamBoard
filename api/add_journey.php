<?php
// Tắt lỗi HTML để trả về JSON sạch
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../app/controllers/JournalController.php';

$controller = new JournalController();
$controller->addJourney();
?>