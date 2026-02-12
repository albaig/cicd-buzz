<?php
/**
 * Complete End-to-End Example
 * 
 * This example demonstrates the complete user registration flow
 * in a single file for educational purposes.
 */

echo "=======================================================\n";
echo "   PHP USER REGISTRATION LIBRARY - COMPLETE DEMO      \n";
echo "=======================================================\n\n";

// Include the library
require_once __DIR__ . '/../src/UserRegistration.php';

// Load configuration
$config = require __DIR__ . '/../config/config.example.php';

// For demo purposes, use SQLite in-memory database
try {
    $db = new PDO('sqlite::memory:');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create table (modified for SQLite)
    $db->exec("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(255) UNIQUE NOT NULL,
            phone_number VARCHAR(20),
            email_verified BOOLEAN DEFAULT 0,
            phone_verified BOOLEAN DEFAULT 0,
            email_verification_token VARCHAR(255),
            email_verification_expires DATETIME,
            phone_otp VARCHAR(6),
            phone_otp_expires DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "✓ Database initialized\n\n";
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}

// Initialize UserRegistration
$userReg = new UserRegistration($db, $config);

// Simulation variables
$testEmail = 'john.doe@example.com';
$testPhone = '+1234567890';

echo "=======================================================\n";
echo "STEP 1: USER ENTERS EMAIL AND SUBMITS REGISTRATION\n";
echo "=======================================================\n\n";

echo "User enters: $testEmail\n";
echo "Clicking 'Register' button...\n\n";

$result = $userReg->startRegistration($testEmail);

if ($result['success']) {
    echo "✓ " . $result['message'] . "\n";
    echo "✓ User ID created: {$result['user_id']}\n";
    $userId = $result['user_id'];
} else {
    die("✗ Registration failed: " . $result['message'] . "\n");
}

// Get the verification token from database
$stmt = $db->prepare("SELECT email_verification_token FROM users WHERE id = ?");
$stmt->execute([$userId]);
$tokenData = $stmt->fetch();
$emailToken = $tokenData['email_verification_token'];

echo "\n📧 Email sent to: $testEmail\n";
echo "   Subject: Verify Your Email Address\n";
echo "   Link: http://localhost/verify-email.php?token=$emailToken\n";

echo "\n\n=======================================================\n";
echo "STEP 2: USER CLICKS VERIFICATION LINK IN EMAIL\n";
echo "=======================================================\n\n";

echo "User clicks the verification link...\n\n";

$result = $userReg->verifyEmail($emailToken);

if ($result['success']) {
    echo "✓ " . $result['message'] . "\n";
    echo "✓ Email verified for: {$result['email']}\n";
} else {
    die("✗ Email verification failed: " . $result['message'] . "\n");
}

// Check status after email verification
$status = $userReg->getUserStatus($userId);
echo "\n📊 User Status:\n";
echo "   Email Verified: " . ($status['user']['email_verified'] ? 'Yes ✓' : 'No ✗') . "\n";
echo "   Phone Verified: " . ($status['user']['phone_verified'] ? 'Yes ✓' : 'No ✗') . "\n";

echo "\n\n=======================================================\n";
echo "STEP 3: USER ENTERS PHONE NUMBER\n";
echo "=======================================================\n\n";

echo "User enters phone number: $testPhone\n";
echo "Clicking 'Send OTP' button...\n\n";

$result = $userReg->startPhoneVerification($userId, $testPhone);

if ($result['success']) {
    echo "✓ " . $result['message'] . "\n";
    if (isset($result['otp_for_testing'])) {
        $otp = $result['otp_for_testing'];
        echo "✓ OTP generated: $otp (shown in debug mode)\n";
    }
} else {
    die("✗ Phone verification failed: " . $result['message'] . "\n");
}

// Get OTP from database
$stmt = $db->prepare("SELECT phone_otp FROM users WHERE id = ?");
$stmt->execute([$userId]);
$otpData = $stmt->fetch();
$otp = $otpData['phone_otp'];

echo "\n📱 SMS sent to: $testPhone\n";
echo "   Message: Your verification code is: $otp\n";

echo "\n\n=======================================================\n";
echo "STEP 4: USER ENTERS OTP CODE\n";
echo "=======================================================\n\n";

echo "User enters OTP: $otp\n";
echo "Clicking 'Verify' button...\n\n";

$result = $userReg->verifyPhone($userId, $otp);

if ($result['success']) {
    echo "✓ " . $result['message'] . "\n";
} else {
    die("✗ Phone verification failed: " . $result['message'] . "\n");
}

// Final status check
$status = $userReg->getUserStatus($userId);

echo "\n\n=======================================================\n";
echo "FINAL STATUS - REGISTRATION COMPLETE!\n";
echo "=======================================================\n\n";

echo "👤 User Information:\n";
echo "   ID: {$status['user']['id']}\n";
echo "   Email: {$status['user']['email']}\n";
echo "   Phone: {$status['user']['phone_number']}\n\n";

echo "✓ Verification Status:\n";
echo "   Email Verified: " . ($status['user']['email_verified'] ? 'Yes ✓' : 'No ✗') . "\n";
echo "   Phone Verified: " . ($status['user']['phone_verified'] ? 'Yes ✓' : 'No ✗') . "\n";
echo "   Fully Verified: " . ($status['user']['fully_verified'] ? 'Yes ✓✓' : 'No ✗') . "\n\n";

if ($status['user']['fully_verified']) {
    echo "🎉 SUCCESS! User is now fully registered and verified!\n";
    echo "   The user can now access all features of your application.\n";
}

echo "\n=======================================================\n";
echo "WORKFLOW SUMMARY\n";
echo "=======================================================\n\n";

echo "1. User Registration Started     ✓\n";
echo "2. Verification Email Sent       ✓\n";
echo "3. Email Link Clicked            ✓\n";
echo "4. Email Verified                ✓\n";
echo "5. Phone Number Entered          ✓\n";
echo "6. OTP Sent via SMS              ✓\n";
echo "7. OTP Verified                  ✓\n";
echo "8. Registration Complete         ✓✓\n\n";

echo "=======================================================\n";
echo "This library provides everything you need for secure\n";
echo "user registration with email and phone verification!\n";
echo "=======================================================\n\n";

echo "Next Steps:\n";
echo "- Integrate with your application\n";
echo "- Configure email service (SendGrid, SES, etc.)\n";
echo "- Configure SMS service (Twilio, etc.)\n";
echo "- Deploy to production\n";
echo "- Monitor and maintain\n\n";

echo "Documentation:\n";
echo "- README.md - Full documentation\n";
echo "- QUICKSTART.md - 5-minute setup guide\n";
echo "- INTEGRATION.md - Production deployment\n";
echo "- PROJECT_OVERVIEW.md - Complete overview\n\n";
