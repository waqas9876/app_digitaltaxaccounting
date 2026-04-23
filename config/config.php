<?php
// =============================================
// APPLICATION CONFIGURATION
// =============================================

define('APP_NAME', 'Digital Tax Accounting');
define('APP_URL', 'https://app.digitaltaxaccounting.com');
define('APP_VERSION', '1.0.0');

// Colors
define('COLOR_PRIMARY', '#FF7421');
define('COLOR_DARK', '#16295A');
define('COLOR_WHITE', '#FFFFFF');

// Session
define('SESSION_LIFETIME', 86400); // 24 hours
define('REMEMBER_LIFETIME', 2592000); // 30 days

// Upload settings
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']);

// SMTP Email Settings
define('SMTP_HOST',      'smtp.gmail.com');   // Gmail: smtp.gmail.com | Hostinger: mail.yourdomain.com
define('SMTP_PORT',      587);                // 587 = TLS, 465 = SSL
define('SMTP_SECURE',    'tls');              // 'tls' or 'ssl'
define('SMTP_USER',      'your@gmail.com');   // Your email address
define('SMTP_PASS',      'your-app-password');// Gmail: 16-char App Password | Hostinger: email password
define('SMTP_FROM',      'your@gmail.com');   // Sender email
define('SMTP_FROM_NAME', APP_NAME);           // Sender name

// Tax rate (HMRC standard mileage rate)
define('STANDARD_MILEAGE_RATE', 0.67);

// Timezone
date_default_timezone_set('Europe/London');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => false, // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
