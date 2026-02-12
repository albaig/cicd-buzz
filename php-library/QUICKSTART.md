# Quick Start Guide

Get started with the User Registration Library in 5 minutes!

## Prerequisites

- PHP 7.4 or higher
- MySQL or MariaDB database
- Web server (Apache, Nginx, or PHP built-in server)

## Step 1: Database Setup (2 minutes)

1. Create a new database:

```sql
CREATE DATABASE user_registration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Create a database user (optional but recommended):

```sql
CREATE USER 'registration_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON user_registration.* TO 'registration_user'@'localhost';
FLUSH PRIVILEGES;
```

## Step 2: Configuration (1 minute)

1. Navigate to the config directory:

```bash
cd php-library/config
```

2. Copy the example configuration:

```bash
cp config.example.php config.php
```

3. Edit `config.php` with your database credentials:

```php
return [
    'db_host' => 'localhost',
    'db_name' => 'user_registration',
    'db_user' => 'registration_user',
    'db_pass' => 'your_secure_password',
    'db_charset' => 'utf8mb4',
    
    'base_url' => 'http://localhost:8000',
    'from_email' => 'noreply@localhost',
    'debug_mode' => true,
];
```

## Step 3: Initialize Database (30 seconds)

Create a file `init.php` in the `examples` directory:

```php
<?php
require_once __DIR__ . '/../src/UserRegistration.php';

$config = require __DIR__ . '/../config/config.php';

$db = new PDO(
    "mysql:host={$config['db_host']};dbname={$config['db_name']};charset={$config['db_charset']}",
    $config['db_user'],
    $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$userReg = new UserRegistration($db, $config);
$userReg->initializeDatabase();

echo "Database initialized successfully!";
?>
```

Run it:

```bash
php examples/init.php
```

## Step 4: Start the Web Server (30 seconds)

Using PHP's built-in server:

```bash
cd php-library/examples
php -S localhost:8000
```

## Step 5: Test It! (1 minute)

1. Open your browser and go to:
   ```
   http://localhost:8000/register.php
   ```

2. Enter an email address and submit

3. Check the console/terminal - you'll see the verification link (in debug mode)

4. Copy the verification link and paste it in your browser

5. Enter a phone number (e.g., +1234567890)

6. You'll see the OTP in the console (in debug mode)

7. Enter the OTP to complete registration

8. Success! Your user is now fully verified! 🎉

## What's Next?

### For Development

- Keep `debug_mode` set to `true`
- Verification emails and SMS will be logged to the console
- You can see tokens and OTPs for testing

### For Production

1. **Disable Debug Mode**:
   ```php
   'debug_mode' => false,
   ```

2. **Set up Email Service**:
   - Sign up for SendGrid, Amazon SES, or similar
   - Update the `sendVerificationEmail()` method

3. **Set up SMS Service**:
   - Sign up for Twilio (recommended)
   - Add your credentials to config:
     ```php
     'twilio_sid' => 'your_sid',
     'twilio_token' => 'your_token',
     'twilio_phone' => '+1234567890',
     ```

4. **Use HTTPS**:
   - Get an SSL certificate
   - Update `base_url` to use https://

5. **Add Security**:
   - Implement rate limiting
   - Add CAPTCHA to forms
   - Use environment variables for sensitive data

## Common Use Cases

### Integrate with Existing Login System

```php
<?php
// After successful phone verification
$userId = $result['user_id'];

// Set your application's session
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = $userId;

// Redirect to dashboard
header('Location: /dashboard');
?>
```

### API Integration

Use the included `api.php` for REST API access:

```javascript
// JavaScript/React/Vue/Angular
async function register(email) {
    const response = await fetch('http://localhost:8000/api.php?action=register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
    });
    
    const data = await response.json();
    if (data.success) {
        alert('Verification email sent!');
    }
}
```

### Check User Status

```php
<?php
$status = $userReg->getUserStatus($userId);

if ($status['user']['fully_verified']) {
    echo "Welcome! You have full access.";
} elseif ($status['user']['email_verified']) {
    echo "Please verify your phone number.";
} else {
    echo "Please verify your email first.";
}
?>
```

## Troubleshooting

### "Database connection failed"
- Check your database credentials in `config.php`
- Make sure MySQL/MariaDB is running
- Verify the database exists

### "Email not sending"
- In debug mode, emails are logged to console (not actually sent)
- Set `debug_mode` to false to use actual email sending
- Configure a proper email service for production

### "OTP not received"
- In debug mode, OTP is displayed in the console
- Set `debug_mode` to false to use actual SMS sending
- Configure Twilio or another SMS service for production

### "Token/OTP expired"
- Email tokens expire after 24 hours
- Phone OTPs expire after 10 minutes
- Request a new verification email/OTP

## Need Help?

- Read the full [README.md](README.md)
- Check the [INTEGRATION.md](INTEGRATION.md) guide
- Review the example files in the `examples/` directory
- Run the test script: `php examples/test.php`

## Summary

You now have a fully functional user registration system with:

✅ Email verification
✅ Phone verification with OTP
✅ Secure token generation
✅ Expiration handling
✅ User status tracking
✅ Clean, reusable code

Happy coding! 🚀
