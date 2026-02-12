<?php
/**
 * Example: Phone Verification
 * 
 * This example handles phone number verification with OTP
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

// Get user ID from session or URL
$userId = $_SESSION['user_id'] ?? $_GET['user_id'] ?? null;

if (!$userId) {
    header('Location: register.php');
    exit;
}

// Check user status
$status = $userReg->getUserStatus($userId);
if (!$status['success'] || !$status['user']['email_verified']) {
    die("Email must be verified first");
}

$otpSent = false;
$message = '';
$error = '';
$verified = false;
$debugOtp = null;

// Handle phone number submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone_number'])) {
    $phoneNumber = $_POST['phone_number'];
    $result = $userReg->startPhoneVerification($userId, $phoneNumber);
    
    if ($result['success']) {
        $message = $result['message'];
        $otpSent = true;
        $debugOtp = $result['otp_for_testing'] ?? null;
        $_SESSION['otp_sent'] = true;
    } else {
        $error = $result['message'];
    }
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $otp = $_POST['otp'];
    $result = $userReg->verifyPhone($userId, $otp);
    
    if ($result['success']) {
        $message = $result['message'];
        $verified = true;
        unset($_SESSION['otp_sent']);
    } else {
        $error = $result['message'];
    }
}

$otpSent = $otpSent || ($_SESSION['otp_sent'] ?? false);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Verification</title>
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
        input[type="tel"],
        input[type="text"] {
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
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .debug {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 4px;
            margin-top: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Phone Verification</h1>
        
        <?php if ($message): ?>
            <div class="message success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($verified): ?>
            <p><strong>Congratulations!</strong> Your registration is complete.</p>
            <p>You can now access all features of the platform.</p>
        <?php elseif (!$otpSent): ?>
            <div class="info">
                Your email has been verified. Now let's verify your phone number.
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input 
                        type="tel" 
                        id="phone_number" 
                        name="phone_number" 
                        placeholder="+1234567890" 
                        required
                    >
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Include country code (e.g., +1 for US)
                    </small>
                </div>
                
                <button type="submit">Send OTP</button>
            </form>
        <?php else: ?>
            <div class="info">
                An OTP has been sent to your phone number. Please enter it below.
            </div>
            
            <?php if ($debugOtp): ?>
                <div class="debug">
                    <strong>Debug Mode:</strong> Your OTP is: <strong><?php echo htmlspecialchars($debugOtp); ?></strong>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="otp">Enter OTP</label>
                    <input 
                        type="text" 
                        id="otp" 
                        name="otp" 
                        placeholder="Enter 6-digit OTP" 
                        maxlength="6"
                        pattern="[0-9]{6}"
                        required
                    >
                    <small style="color: #666; display: block; margin-top: 5px;">
                        OTP expires in 10 minutes
                    </small>
                </div>
                
                <button type="submit">Verify OTP</button>
            </form>
            
            <p style="margin-top: 15px; text-align: center;">
                <a href="?user_id=<?php echo $userId; ?>" style="color: #4CAF50; text-decoration: none;">
                    Request new OTP
                </a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
