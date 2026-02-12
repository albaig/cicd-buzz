<?php
/**
 * UserRegistration Class
 * 
 * Complete user registration system with email and phone verification
 * 
 * @author CICD-Buzz Team
 * @version 1.0.0
 */

class UserRegistration {
    private $db;
    private $config;
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     * @param array $config Configuration array
     */
    public function __construct($db, $config) {
        $this->db = $db;
        $this->config = $config;
    }
    
    /**
     * Initialize database tables
     * 
     * @return bool Success status
     */
    public function initializeDatabase() {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
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
        )";
        
        try {
            $this->db->exec($sql);
            return true;
        } catch (PDOException $e) {
            error_log("Database initialization error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Start registration process
     * 
     * @param string $email User's email address
     * @return array Response with status and message
     */
    public function startRegistration($email) {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email address'
            ];
        }
        
        // Check if email already exists
        $stmt = $this->db->prepare("SELECT id, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingUser && $existingUser['email_verified']) {
            return [
                'success' => false,
                'message' => 'Email already registered and verified'
            ];
        }
        
        // Generate verification token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        if ($existingUser) {
            // Update existing unverified user
            $stmt = $this->db->prepare("
                UPDATE users 
                SET email_verification_token = ?, 
                    email_verification_expires = ? 
                WHERE email = ?
            ");
            $stmt->execute([$token, $expires, $email]);
            $userId = $existingUser['id'];
        } else {
            // Insert new user
            $stmt = $this->db->prepare("
                INSERT INTO users (email, email_verification_token, email_verification_expires) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$email, $token, $expires]);
            $userId = $this->db->lastInsertId();
        }
        
        // Send verification email
        $emailSent = $this->sendVerificationEmail($email, $token);
        
        if (!$emailSent) {
            return [
                'success' => false,
                'message' => 'Failed to send verification email'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Verification email sent. Please check your inbox.',
            'user_id' => $userId
        ];
    }
    
    /**
     * Verify email with token
     * 
     * @param string $token Verification token
     * @return array Response with status and message
     */
    public function verifyEmail($token) {
        $stmt = $this->db->prepare("
            SELECT id, email, email_verification_expires 
            FROM users 
            WHERE email_verification_token = ? 
            AND email_verified = FALSE
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid or expired verification token'
            ];
        }
        
        // Check if token expired
        if (strtotime($user['email_verification_expires']) < time()) {
            return [
                'success' => false,
                'message' => 'Verification token has expired'
            ];
        }
        
        // Mark email as verified
        $stmt = $this->db->prepare("
            UPDATE users 
            SET email_verified = TRUE, 
                email_verification_token = NULL, 
                email_verification_expires = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        
        return [
            'success' => true,
            'message' => 'Email verified successfully. You can now verify your phone number.',
            'user_id' => $user['id'],
            'email' => $user['email']
        ];
    }
    
    /**
     * Start phone verification
     * 
     * @param int $userId User ID
     * @param string $phoneNumber Phone number
     * @return array Response with status and message
     */
    public function startPhoneVerification($userId, $phoneNumber) {
        // Validate phone number
        $phoneNumber = trim($phoneNumber);
        
        // Remove all characters except digits and +
        $cleanedNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Validate format:
        // - Must start with + or digit
        // - Only one + allowed, and it must be at the start
        // - Must have at least 10 digits
        if (!preg_match('/^\+?[0-9]{10,}$/', $cleanedNumber)) {
            return [
                'success' => false,
                'message' => 'Invalid phone number format. Please use international format (e.g., +1234567890)'
            ];
        }
        
        // Use the cleaned number
        $phoneNumber = $cleanedNumber;
        
        // Check if user exists and email is verified
        $stmt = $this->db->prepare("
            SELECT id, email_verified, phone_verified 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
        
        if (!$user['email_verified']) {
            return [
                'success' => false,
                'message' => 'Email must be verified before phone verification'
            ];
        }
        
        if ($user['phone_verified']) {
            return [
                'success' => false,
                'message' => 'Phone number already verified'
            ];
        }
        
        // Generate 6-digit OTP (range 100000-999999 to avoid leading zeros)
        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Update user with phone number and OTP
        $stmt = $this->db->prepare("
            UPDATE users 
            SET phone_number = ?, 
                phone_otp = ?, 
                phone_otp_expires = ? 
            WHERE id = ?
        ");
        $stmt->execute([$phoneNumber, $otp, $expires, $userId]);
        
        // Send OTP via SMS
        $smsSent = $this->sendOTP($phoneNumber, $otp);
        
        if (!$smsSent) {
            return [
                'success' => false,
                'message' => 'Failed to send OTP'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'OTP sent to your phone number',
            'otp_for_testing' => $this->config['debug_mode'] ? $otp : null
        ];
    }
    
    /**
     * Verify phone with OTP
     * 
     * @param int $userId User ID
     * @param string $otp OTP code
     * @return array Response with status and message
     */
    public function verifyPhone($userId, $otp) {
        $stmt = $this->db->prepare("
            SELECT id, phone_otp, phone_otp_expires, phone_verified 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
        
        if ($user['phone_verified']) {
            return [
                'success' => false,
                'message' => 'Phone number already verified'
            ];
        }
        
        if (!$user['phone_otp']) {
            return [
                'success' => false,
                'message' => 'No OTP found. Please request a new OTP.'
            ];
        }
        
        // Check if OTP expired
        if (strtotime($user['phone_otp_expires']) < time()) {
            return [
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.'
            ];
        }
        
        // Verify OTP
        if ($user['phone_otp'] !== $otp) {
            return [
                'success' => false,
                'message' => 'Invalid OTP'
            ];
        }
        
        // Mark phone as verified
        $stmt = $this->db->prepare("
            UPDATE users 
            SET phone_verified = TRUE, 
                phone_otp = NULL, 
                phone_otp_expires = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        
        return [
            'success' => true,
            'message' => 'Phone number verified successfully. Registration complete!',
            'user_id' => $user['id']
        ];
    }
    
    /**
     * Get user status
     * 
     * @param int $userId User ID
     * @return array User status information
     */
    public function getUserStatus($userId) {
        $stmt = $this->db->prepare("
            SELECT id, email, phone_number, email_verified, phone_verified 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'phone_number' => $user['phone_number'],
                'email_verified' => (bool)$user['email_verified'],
                'phone_verified' => (bool)$user['phone_verified'],
                'fully_verified' => (bool)($user['email_verified'] && $user['phone_verified'])
            ]
        ];
    }
    
    /**
     * Send verification email
     * 
     * @param string $email Email address
     * @param string $token Verification token
     * @return bool Success status
     */
    private function sendVerificationEmail($email, $token) {
        $verificationUrl = $this->config['base_url'] . '/verify-email.php?token=' . $token;
        
        $subject = 'Verify Your Email Address';
        $message = "
        <html>
        <head>
            <title>Email Verification</title>
        </head>
        <body>
            <h2>Welcome to our platform!</h2>
            <p>Please click the link below to verify your email address:</p>
            <p><a href='{$verificationUrl}'>Verify Email Address</a></p>
            <p>Or copy and paste this link in your browser:</p>
            <p>{$verificationUrl}</p>
            <p>This link will expire in 24 hours.</p>
            <p>If you did not request this, please ignore this email.</p>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: " . $this->config['from_email'] . "\r\n";
        
        // In production, use a proper email service
        if ($this->config['debug_mode']) {
            error_log("Verification email would be sent to: $email");
            error_log("Verification URL: $verificationUrl");
            return true;
        }
        
        return mail($email, $subject, $message, $headers);
    }
    
    /**
     * Send OTP via SMS
     * 
     * @param string $phoneNumber Phone number
     * @param string $otp OTP code
     * @return bool Success status
     */
    private function sendOTP($phoneNumber, $otp) {
        // In production, integrate with SMS service like Twilio, AWS SNS, etc.
        
        if ($this->config['debug_mode']) {
            error_log("OTP would be sent to: $phoneNumber");
            error_log("OTP: $otp");
            return true;
        }
        
        // Example Twilio integration (requires Twilio SDK)
        if (isset($this->config['twilio_sid']) && isset($this->config['twilio_token'])) {
            try {
                // Placeholder for Twilio integration
                // $client = new Twilio\Rest\Client($this->config['twilio_sid'], $this->config['twilio_token']);
                // $message = $client->messages->create(
                //     $phoneNumber,
                //     [
                //         'from' => $this->config['twilio_phone'],
                //         'body' => "Your verification code is: $otp"
                //     ]
                // );
                return true;
            } catch (Exception $e) {
                error_log("SMS sending error: " . $e->getMessage());
                return false;
            }
        }
        
        return true;
    }
}
