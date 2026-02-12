<?php
/**
 * Example: Start Registration
 * 
 * This example shows how to start the user registration process
 */

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
    die("Database connection failed: " . $e->getMessage());
}

// Initialize UserRegistration
$userReg = new UserRegistration($db, $config);

// Initialize database tables (only needed once)
$userReg->initializeDatabase();

// Start session before using session variables
session_start();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $result = $userReg->startRegistration($email);
    
    if ($result['success']) {
        $_SESSION['registration_message'] = $result['message'];
        $_SESSION['user_id'] = $result['user_id'];
    } else {
        $_SESSION['registration_error'] = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>User Registration</h1>
        
        <?php if (isset($_SESSION['registration_message'])): ?>
            <div class="message success">
                <?php 
                echo htmlspecialchars($_SESSION['registration_message']); 
                unset($_SESSION['registration_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['registration_error'])): ?>
            <div class="message error">
                <?php 
                echo htmlspecialchars($_SESSION['registration_error']); 
                unset($_SESSION['registration_error']);
                ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="Enter your email address" 
                    required
                >
            </div>
            
            <button type="submit">Start Registration</button>
        </form>
        
        <p style="margin-top: 20px; color: #666; font-size: 14px;">
            After submitting, you will receive a verification email. Click the link in the email to verify your address.
        </p>
    </div>
</body>
</html>
