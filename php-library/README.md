# PHP User Registration Library

A complete, production-ready PHP library for user registration with email and phone verification.

## Features

- ✅ Email-based registration
- ✅ Email verification with secure tokens
- ✅ Phone number verification with OTP
- ✅ Secure token and OTP generation
- ✅ Expiration handling for tokens and OTPs
- ✅ User status tracking
- ✅ Database schema management
- ✅ Easy integration with any PHP application
- ✅ RESTful API support
- ✅ Debug mode for development

## Table of Contents

- [Installation](#installation)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Basic Usage](#basic-usage)
  - [Web Interface](#web-interface)
  - [API Usage](#api-usage)
- [Workflow](#workflow)
- [API Reference](#api-reference)
- [Security Considerations](#security-considerations)
- [Production Setup](#production-setup)

## Installation

1. Copy the `php-library` directory to your project:

```bash
cp -r php-library /path/to/your/project/
```

2. Include the library in your PHP file:

```php
require_once '/path/to/php-library/src/UserRegistration.php';
```

## Database Setup

The library uses MySQL/MariaDB. Create a database and configure the connection:

```sql
CREATE DATABASE user_registration;
```

The library will automatically create the required table when you call `initializeDatabase()`:

```php
$userReg->initializeDatabase();
```

### Database Schema

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone_number VARCHAR(20),
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255),
    email_verification_expires DATETIME,
    phone_otp VARCHAR(6),
    phone_otp_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Configuration

1. Copy the example configuration file:

```bash
cp php-library/config/config.example.php php-library/config/config.php
```

2. Update the configuration with your settings:

```php
return [
    // Database Configuration
    'db_host' => 'localhost',
    'db_name' => 'user_registration',
    'db_user' => 'your_username',
    'db_pass' => 'your_password',
    'db_charset' => 'utf8mb4',
    
    // Application Settings
    'base_url' => 'http://yourdomain.com',
    'from_email' => 'noreply@yourdomain.com',
    'debug_mode' => false, // Set to false in production
    
    // SMS/Twilio Configuration (optional)
    'twilio_sid' => 'your_twilio_sid',
    'twilio_token' => 'your_twilio_token',
    'twilio_phone' => '+1234567890',
];
```

## Usage

### Basic Usage

```php
<?php
require_once 'php-library/src/UserRegistration.php';

// Load configuration
$config = require 'php-library/config/config.php';

// Create database connection
$db = new PDO(
    "mysql:host={$config['db_host']};dbname={$config['db_name']};charset={$config['db_charset']}",
    $config['db_user'],
    $config['db_pass'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

// Initialize the library
$userReg = new UserRegistration($db, $config);

// Initialize database (only needed once)
$userReg->initializeDatabase();

// Step 1: Start registration
$result = $userReg->startRegistration('user@example.com');
if ($result['success']) {
    $userId = $result['user_id'];
    echo "Verification email sent!";
}

// Step 2: Verify email (when user clicks the link)
$result = $userReg->verifyEmail($token);
if ($result['success']) {
    echo "Email verified!";
}

// Step 3: Start phone verification
$result = $userReg->startPhoneVerification($userId, '+1234567890');
if ($result['success']) {
    echo "OTP sent!";
}

// Step 4: Verify phone with OTP
$result = $userReg->verifyPhone($userId, '123456');
if ($result['success']) {
    echo "Registration complete!";
}

// Check user status
$status = $userReg->getUserStatus($userId);
if ($status['user']['fully_verified']) {
    echo "User is fully verified!";
}
?>
```

### Web Interface

The library includes ready-to-use web interface examples:

1. **Registration Page** (`examples/register.php`)
   - User enters email address
   - System sends verification email

2. **Email Verification** (`examples/verify-email.php`)
   - Handles email verification via token
   - Redirects to phone verification

3. **Phone Verification** (`examples/verify-phone.php`)
   - User enters phone number
   - System sends OTP
   - User enters OTP to complete verification

Simply place these files in your web directory and configure your web server.

### API Usage

The library can also be used via REST API (`examples/api.php`):

#### Start Registration

```bash
curl -X POST http://yourdomain.com/api.php?action=register \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com"}'
```

#### Verify Email

```bash
curl -X POST http://yourdomain.com/api.php?action=verify-email \
  -H "Content-Type: application/json" \
  -d '{"token": "verification_token_here"}'
```

#### Start Phone Verification

```bash
curl -X POST http://yourdomain.com/api.php?action=verify-phone-start \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "phone_number": "+1234567890"}'
```

#### Verify Phone

```bash
curl -X POST http://yourdomain.com/api.php?action=verify-phone \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "otp": "123456"}'
```

#### Get User Status

```bash
curl http://yourdomain.com/api.php?action=status&user_id=1
```

## Workflow

```
1. User Registration
   ↓
2. Email Verification Email Sent
   ↓
3. User Clicks Verification Link
   ↓
4. Email Verified ✓
   ↓
5. Phone Number Entry
   ↓
6. OTP Sent to Phone
   ↓
7. User Enters OTP
   ↓
8. Phone Verified ✓
   ↓
9. Registration Complete ✓✓
```

## API Reference

### `startRegistration($email)`

Starts the registration process by creating a user and sending a verification email.

**Parameters:**
- `$email` (string): User's email address

**Returns:**
```php
[
    'success' => true|false,
    'message' => 'Status message',
    'user_id' => 123 // Only on success
]
```

### `verifyEmail($token)`

Verifies the user's email address using the provided token.

**Parameters:**
- `$token` (string): Email verification token

**Returns:**
```php
[
    'success' => true|false,
    'message' => 'Status message',
    'user_id' => 123,
    'email' => 'user@example.com'
]
```

### `startPhoneVerification($userId, $phoneNumber)`

Starts phone verification by generating and sending an OTP.

**Parameters:**
- `$userId` (int): User ID
- `$phoneNumber` (string): Phone number with country code

**Returns:**
```php
[
    'success' => true|false,
    'message' => 'Status message',
    'otp_for_testing' => '123456' // Only in debug mode
]
```

### `verifyPhone($userId, $otp)`

Verifies the user's phone number using the provided OTP.

**Parameters:**
- `$userId` (int): User ID
- `$otp` (string): 6-digit OTP code

**Returns:**
```php
[
    'success' => true|false,
    'message' => 'Status message',
    'user_id' => 123
]
```

### `getUserStatus($userId)`

Retrieves the current verification status of a user.

**Parameters:**
- `$userId` (int): User ID

**Returns:**
```php
[
    'success' => true|false,
    'user' => [
        'id' => 123,
        'email' => 'user@example.com',
        'phone_number' => '+1234567890',
        'email_verified' => true|false,
        'phone_verified' => true|false,
        'fully_verified' => true|false
    ]
]
```

## Security Considerations

1. **Tokens**: Email verification tokens are 64-character hexadecimal strings (32 random bytes)
2. **OTP**: Phone OTPs are 6-digit random numbers
3. **Expiration**: 
   - Email tokens expire after 24 hours
   - Phone OTPs expire after 10 minutes
4. **Database**: Uses prepared statements to prevent SQL injection
5. **Input Validation**: All inputs are validated and sanitized
6. **HTTPS**: Always use HTTPS in production to protect sensitive data

## Production Setup

### Email Configuration

For production, integrate with a proper email service provider:

- **SendGrid**
- **Amazon SES**
- **Mailgun**
- **Postmark**

Replace the `sendVerificationEmail()` method with your provider's API.

### SMS Configuration

For production SMS/OTP delivery, integrate with:

- **Twilio** (example included)
- **AWS SNS**
- **Nexmo/Vonage**
- **Plivo**

Update the `sendOTP()` method with your provider's API.

### Environment Variables

Store sensitive configuration in environment variables:

```php
$config = [
    'db_host' => getenv('DB_HOST'),
    'db_name' => getenv('DB_NAME'),
    'db_user' => getenv('DB_USER'),
    'db_pass' => getenv('DB_PASS'),
    'twilio_sid' => getenv('TWILIO_SID'),
    'twilio_token' => getenv('TWILIO_TOKEN'),
    'debug_mode' => false,
];
```

### Additional Security

1. Implement rate limiting to prevent abuse
2. Add CAPTCHA to registration form
3. Implement IP-based restrictions
4. Add logging for security events
5. Use SSL/TLS for database connections
6. Implement password hashing if adding password authentication

## License

MIT License - Feel free to use this library in your projects.

## Support

For issues, questions, or contributions, please visit the repository.
