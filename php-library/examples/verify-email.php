<?php
/**
 * Example: Verify Email
 * 
 * This example handles email verification via token
 */

session_start();

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

// Handle email verification
$message = '';
$error = '';
$verified = false;
$userId = null;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $result = $userReg->verifyEmail($token);
    
    if ($result['success']) {
        $message = $result['message'];
        $verified = true;
        $_SESSION['user_id'] = $result['user_id'];
        $userId = $result['user_id'];
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
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
        .message {
            padding: 15px;
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
        .btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 16px;
            margin-top: 10px;
        }
        .btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Email Verification</h1>
        
        <?php if ($verified): ?>
            <div class="message success">
                <strong>Success!</strong><br>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <p>You can now proceed to verify your phone number.</p>
            <a href="verify-phone.php?user_id=<?php echo $userId; ?>" class="btn">Verify Phone Number</a>
        <?php elseif ($error): ?>
            <div class="message error">
                <strong>Error!</strong><br>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <a href="register.php" class="btn">Back to Registration</a>
        <?php else: ?>
            <p>No verification token provided.</p>
            <a href="register.php" class="btn">Back to Registration</a>
        <?php endif; ?>
    </div>
</body>
</html>
