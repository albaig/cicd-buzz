<?php
/**
 * Configuration file for User Registration Library
 * 
 * Copy this file to config.php and update with your settings
 */

return [
    // Database Configuration
    'db_host' => 'localhost',
    'db_name' => 'user_registration',
    'db_user' => 'root',
    'db_pass' => '',
    'db_charset' => 'utf8mb4',
    
    // Application Settings
    'base_url' => 'http://localhost/your-app',
    'from_email' => 'noreply@yourapp.com',
    'debug_mode' => true,
    
    // SMS/Twilio Configuration (optional)
    'twilio_sid' => 'your_twilio_sid',
    'twilio_token' => 'your_twilio_token',
    'twilio_phone' => '+1234567890',
    
    // Session Configuration
    'session_name' => 'user_registration_session',
    'session_lifetime' => 3600, // 1 hour
];
