<?php
session_start();
require_once 'app/controllers/AuthController.php';
$auth = new AuthController();
$action = $_GET['action'] ?? 'login'; 
switch ($action) {
    case 'login':
        $auth->login();
        break;

    case 'register':
        $auth->register();
        break;
        
    case 'logout':
        $auth->logout();
        break;

    case 'send_otp':
        $auth->sendOtp(); 
        break;

    case 'verify_otp':
        $auth->verifyOtp(); 
        break;

    case 'reset_password':
        $auth->resetPassword(); 
        break;

    default:
        $auth->login(); 
        break;
}
?>