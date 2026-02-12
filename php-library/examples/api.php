<?php
/**
 * Simple API Example
 * 
 * This example shows how to use the library via API endpoints
 */

header('Content-Type: application/json');

// Include the library
require_once __DIR__ . '/../src/UserRegistration.php';

// Load configuration
$config = require __DIR__ . '/../config/config.example.php';

// Create database connection
try {
    $db = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset={$config['db_charset']}",
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Initialize UserRegistration
$userReg = new UserRegistration($db, $config);

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'register':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        
        $result = $userReg->startRegistration($email);
        echo json_encode($result);
        break;
        
    case 'verify-email':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['token'] ?? '';
        
        $result = $userReg->verifyEmail($token);
        echo json_encode($result);
        break;
        
    case 'verify-phone-start':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? 0;
        $phoneNumber = $input['phone_number'] ?? '';
        
        $result = $userReg->startPhoneVerification($userId, $phoneNumber);
        echo json_encode($result);
        break;
        
    case 'verify-phone':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? 0;
        $otp = $input['otp'] ?? '';
        
        $result = $userReg->verifyPhone($userId, $otp);
        echo json_encode($result);
        break;
        
    case 'status':
        if ($method !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $userId = $_GET['user_id'] ?? 0;
        
        $result = $userReg->getUserStatus($userId);
        echo json_encode($result);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
