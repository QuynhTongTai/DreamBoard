<?php
// Tắt lỗi HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../app/controllers/JournalController.php';

$controller = new JournalController();
$controller->updateJourney();
?>