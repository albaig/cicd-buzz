# Integration Guide

This guide shows you how to integrate the User Registration Library into your PHP application.

## Quick Start (5 minutes)

### 1. Copy the Library

```bash
cp -r php-library /path/to/your/project/
```

### 2. Create Configuration

```bash
cd /path/to/your/project/php-library/config
cp config.example.php config.php
```

Edit `config.php` with your database credentials.

### 3. Initialize Database

```php
<?php
require_once 'php-library/src/UserRegistration.php';

$config = require 'php-library/config/config.php';

$db = new PDO(
    "mysql:host={$config['db_host']};dbname={$config['db_name']};charset={$config['db_charset']}",
    $config['db_user'],
    $config['db_pass']
);

$userReg = new UserRegistration($db, $config);
$userReg->initializeDatabase();

echo "Database initialized!";
?>
```

### 4. Use the Library

Copy one of the examples or create your own:

```php
<?php
require_once 'php-library/src/UserRegistration.php';

// Load config and create DB connection
$config = require 'php-library/config/config.php';
$db = new PDO(/* ... */);

// Initialize
$userReg = new UserRegistration($db, $config);

// Start registration
$result = $userReg->startRegistration($_POST['email']);

if ($result['success']) {
    echo "Verification email sent!";
}
?>
```

## Integration Options

### Option 1: Web Interface (Recommended for Beginners)

Use the provided example files:

1. Copy `examples/register.php` to your web directory
2. Copy `examples/verify-email.php` to your web directory
3. Copy `examples/verify-phone.php` to your web directory
4. Update the configuration
5. Done!

### Option 2: API Integration (Recommended for SPAs/Mobile Apps)

Use the API endpoint:

1. Copy `examples/api.php` to your web directory
2. Configure CORS if needed
3. Make API calls from your frontend

Example JavaScript:

```javascript
// Start registration
const response = await fetch('/api.php?action=register', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email: 'user@example.com' })
});

const result = await response.json();
console.log(result.message);
```

### Option 3: Custom Integration

Include the library in your existing application:

```php
<?php
// In your registration controller/handler

require_once 'php-library/src/UserRegistration.php';

class RegistrationController {
    private $userReg;
    
    public function __construct() {
        $config = require 'config/config.php';
        $db = Database::getConnection(); // Your DB connection
        $this->userReg = new UserRegistration($db, $config);
    }
    
    public function register() {
        $email = $_POST['email'];
        $result = $this->userReg->startRegistration($email);
        
        if ($result['success']) {
            // Redirect or show success
            $_SESSION['user_id'] = $result['user_id'];
            redirect('/verify-email');
        } else {
            // Show error
            showError($result['message']);
        }
    }
    
    public function verifyEmail() {
        $token = $_GET['token'];
        $result = $this->userReg->verifyEmail($token);
        
        if ($result['success']) {
            redirect('/verify-phone');
        }
    }
    
    // ... more methods
}
?>
```

## Production Checklist

### Email Service Setup

1. Choose an email service provider:
   - SendGrid (Recommended)
   - Amazon SES
   - Mailgun
   - Postmark

2. Update `sendVerificationEmail()` method:

```php
private function sendVerificationEmail($email, $token) {
    // Example: SendGrid
    $email = new \SendGrid\Mail\Mail();
    $email->setFrom($this->config['from_email']);
    $email->setSubject("Verify Your Email");
    $email->addTo($email);
    $email->addContent("text/html", $this->getEmailTemplate($token));
    
    $sendgrid = new \SendGrid($this->config['sendgrid_api_key']);
    
    try {
        $response = $sendgrid->send($email);
        return $response->statusCode() === 202;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}
```

### SMS Service Setup

1. Choose an SMS provider:
   - Twilio (Recommended - partially integrated)
   - AWS SNS
   - Vonage/Nexmo
   - Plivo

2. Update `sendOTP()` method:

```php
private function sendOTP($phoneNumber, $otp) {
    // Example: Twilio (already included)
    require_once 'vendor/autoload.php';
    
    $client = new \Twilio\Rest\Client(
        $this->config['twilio_sid'],
        $this->config['twilio_token']
    );
    
    try {
        $message = $client->messages->create(
            $phoneNumber,
            [
                'from' => $this->config['twilio_phone'],
                'body' => "Your verification code is: $otp"
            ]
        );
        return true;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}
```

### Security Enhancements

1. **Rate Limiting**: Prevent abuse

```php
// Add to UserRegistration class
private function checkRateLimit($email) {
    $stmt = $this->db->prepare("
        SELECT COUNT(*) as count 
        FROM registration_attempts 
        WHERE email = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$email]);
    $result = $stmt->fetch();
    
    return $result['count'] < 5; // Max 5 attempts per hour
}
```

2. **HTTPS Only**: Update your web server configuration

```apache
# Apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

3. **CAPTCHA**: Add to registration form

```html
<!-- Google reCAPTCHA -->
<div class="g-recaptcha" data-sitekey="your_site_key"></div>
```

4. **Input Sanitization**: Already implemented but verify

5. **Session Security**:

```php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Strict'
]);
```

### Database Configuration

1. **Production Database**:

```php
$config = [
    'db_host' => getenv('DB_HOST'),
    'db_name' => getenv('DB_NAME'),
    'db_user' => getenv('DB_USER'),
    'db_pass' => getenv('DB_PASS'),
    'db_charset' => 'utf8mb4',
];
```

2. **Indexes for Performance**:

```sql
CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_email_token ON users(email_verification_token);
CREATE INDEX idx_email_verified ON users(email_verified);
CREATE INDEX idx_phone_verified ON users(phone_verified);
```

3. **Backup Strategy**: Set up automated backups

## Testing

### Unit Testing

Create PHPUnit tests:

```php
<?php
use PHPUnit\Framework\TestCase;

class UserRegistrationTest extends TestCase {
    private $db;
    private $userReg;
    
    protected function setUp(): void {
        $this->db = new PDO('sqlite::memory:');
        // ... setup
        $this->userReg = new UserRegistration($this->db, $config);
    }
    
    public function testStartRegistration() {
        $result = $this->userReg->startRegistration('test@example.com');
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user_id', $result);
    }
    
    // ... more tests
}
```

### Manual Testing

1. Test email verification flow
2. Test phone verification flow
3. Test error cases
4. Test expired tokens/OTPs
5. Test duplicate registrations

## Monitoring

### Logging

Add comprehensive logging:

```php
private function logEvent($event, $data) {
    error_log(json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'data' => $data
    ]));
}

// Usage
$this->logEvent('registration_started', ['email' => $email]);
$this->logEvent('email_verified', ['user_id' => $userId]);
```

### Metrics

Track important metrics:
- Registration attempts
- Email verification rate
- Phone verification rate
- Time to complete registration
- Failed verification attempts

## Troubleshooting

### Common Issues

1. **Emails not sending**:
   - Check spam folder
   - Verify email service credentials
   - Check server mail logs
   - Ensure debug_mode is false in production

2. **SMS not sending**:
   - Verify SMS service credentials
   - Check phone number format
   - Verify account balance
   - Check SMS service logs

3. **Database connection issues**:
   - Verify credentials
   - Check database server status
   - Ensure proper permissions
   - Check firewall rules

4. **Token/OTP expired**:
   - Check server time
   - Adjust expiration times if needed
   - Implement resend functionality

## Support

For questions or issues:
1. Check the main README.md
2. Review example files
3. Check error logs
4. Contact support

## License

MIT License
