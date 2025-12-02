<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../app/controllers/VisionController.php';

$controller = new VisionController();
$controller->saveBoardData();
?>