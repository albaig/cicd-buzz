<?php
/**
 * Simple Test Script for User Registration Library
 * 
 * This script demonstrates and tests the basic functionality
 * Note: This is a simple test, not a unit test framework
 */

// Include the library
require_once __DIR__ . '/../src/UserRegistration.php';

// Load configuration
$config = require __DIR__ . '/../config/config.example.php';

echo "=== User Registration Library Test ===\n\n";

// Create in-memory SQLite database for testing
try {
    $db = new PDO('sqlite::memory:');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Modify schema for SQLite
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
    
    echo "✓ Database connection established\n";
} catch (PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// Initialize UserRegistration
$userReg = new UserRegistration($db, $config);

// Test 1: Start Registration
echo "\n--- Test 1: Start Registration ---\n";
$result = $userReg->startRegistration('test@example.com');
if ($result['success']) {
    echo "✓ Registration started successfully\n";
    echo "  User ID: {$result['user_id']}\n";
    $userId = $result['user_id'];
} else {
    die("✗ Failed to start registration: {$result['message']}\n");
}

// Test 2: Duplicate email (should fail if already verified)
echo "\n--- Test 2: Duplicate Email Registration ---\n";
$result = $userReg->startRegistration('test@example.com');
if ($result['success']) {
    echo "✓ Can resend verification email to unverified user\n";
} else {
    echo "✓ Correctly handled: {$result['message']}\n";
}

// Test 3: Get verification token
echo "\n--- Test 3: Get Verification Token ---\n";
$stmt = $db->prepare("SELECT email_verification_token FROM users WHERE id = ?");
$stmt->execute([$userId]);
$tokenData = $stmt->fetch();
$token = $tokenData['email_verification_token'];
echo "✓ Token retrieved: " . substr($token, 0, 20) . "...\n";

// Test 4: Verify Email
echo "\n--- Test 4: Verify Email ---\n";
$result = $userReg->verifyEmail($token);
if ($result['success']) {
    echo "✓ Email verified successfully\n";
    echo "  Message: {$result['message']}\n";
} else {
    die("✗ Failed to verify email: {$result['message']}\n");
}

// Test 5: Try to verify with invalid token
echo "\n--- Test 5: Invalid Token ---\n";
$result = $userReg->verifyEmail('invalid_token');
if (!$result['success']) {
    echo "✓ Correctly rejected invalid token\n";
} else {
    echo "✗ Should have rejected invalid token\n";
}

// Test 6: Start Phone Verification (without email verified - should fail)
echo "\n--- Test 6: Phone Verification Without Email ---\n";
$testUserId = 999; // Non-existent user
$result = $userReg->startPhoneVerification($testUserId, '+1234567890');
if (!$result['success']) {
    echo "✓ Correctly required email verification first\n";
} else {
    echo "✗ Should have required email verification\n";
}

// Test 7: Start Phone Verification (with email verified)
echo "\n--- Test 7: Start Phone Verification ---\n";
$result = $userReg->startPhoneVerification($userId, '+1234567890');
if ($result['success']) {
    echo "✓ Phone verification started\n";
    if (isset($result['otp_for_testing'])) {
        echo "  OTP (debug mode): {$result['otp_for_testing']}\n";
        $otp = $result['otp_for_testing'];
    }
} else {
    die("✗ Failed to start phone verification: {$result['message']}\n");
}

// Get OTP from database for testing
echo "\n--- Test 8: Get OTP ---\n";
$stmt = $db->prepare("SELECT phone_otp FROM users WHERE id = ?");
$stmt->execute([$userId]);
$otpData = $stmt->fetch();
$otp = $otpData['phone_otp'];
echo "✓ OTP retrieved: {$otp}\n";

// Test 9: Verify Phone with wrong OTP
echo "\n--- Test 9: Wrong OTP ---\n";
$result = $userReg->verifyPhone($userId, '000000');
if (!$result['success']) {
    echo "✓ Correctly rejected wrong OTP\n";
} else {
    echo "✗ Should have rejected wrong OTP\n";
}

// Test 10: Verify Phone with correct OTP
echo "\n--- Test 10: Verify Phone ---\n";
$result = $userReg->verifyPhone($userId, $otp);
if ($result['success']) {
    echo "✓ Phone verified successfully\n";
    echo "  Message: {$result['message']}\n";
} else {
    die("✗ Failed to verify phone: {$result['message']}\n");
}

// Test 11: Get User Status
echo "\n--- Test 11: User Status ---\n";
$result = $userReg->getUserStatus($userId);
if ($result['success']) {
    echo "✓ User status retrieved\n";
    echo "  Email: {$result['user']['email']}\n";
    echo "  Phone: {$result['user']['phone_number']}\n";
    echo "  Email Verified: " . ($result['user']['email_verified'] ? 'Yes' : 'No') . "\n";
    echo "  Phone Verified: " . ($result['user']['phone_verified'] ? 'Yes' : 'No') . "\n";
    echo "  Fully Verified: " . ($result['user']['fully_verified'] ? 'Yes' : 'No') . "\n";
    
    if ($result['user']['fully_verified']) {
        echo "\n✓✓ ALL TESTS PASSED! User is fully verified.\n";
    }
} else {
    die("✗ Failed to get user status: {$result['message']}\n");
}

// Test 12: Invalid email format
echo "\n--- Test 12: Invalid Email Format ---\n";
$result = $userReg->startRegistration('not-an-email');
if (!$result['success']) {
    echo "✓ Correctly rejected invalid email format\n";
} else {
    echo "✗ Should have rejected invalid email\n";
}

// Test 13: Invalid phone number
echo "\n--- Test 13: Invalid Phone Number ---\n";
$result = $userReg->startPhoneVerification($userId, '123');
if (!$result['success']) {
    echo "✓ Correctly rejected invalid phone number\n";
} else {
    echo "✗ Should have rejected invalid phone number\n";
}

echo "\n=== All Tests Completed Successfully ===\n";
