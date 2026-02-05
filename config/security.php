<?php
/**
 * Security Configuration
 * Applied to all PHP files in the application
 */

// ====================
// DISABLE ERROR DISPLAY (Production)
// ====================
if ($_SERVER['HTTP_HOST'] !== 'localhost' && strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === false) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php-errors.log');
}

// ====================
// SESSION SECURITY
// ====================
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID to prevent session fixation
if (!isset($_SESSION['_initiated'])) {
    session_regenerate_id(true);
    $_SESSION['_initiated'] = true;
}

// ====================
// PREVENT COMMON VULNERABILITIES
// ====================

// Prevent MIME type sniffing
header('X-Content-Type-Options: nosniff');

// Prevent clickjacking
header('X-Frame-Options: SAMEORIGIN');

// Enable XSS protection in older browsers
header('X-XSS-Protection: 1; mode=block');

// Content Security Policy (CSP)
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://embed.tawk.to https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://embed.tawk.to; img-src 'self' data: https: blob:; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com https://embed.tawk.to; connect-src 'self' https: ws: wss: https://embed.tawk.to; frame-src 'self' https://embed.tawk.to;");

// Referrer Policy
header('Referrer-Policy: strict-origin-when-cross-origin');

// Feature Policy
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// ====================
// HIDE SERVER INFORMATION
// ====================
header_remove('Server');
header_remove('X-Powered-By');
header('X-Powered-By: Oikos');

// ====================
// SECURITY CONSTANTS
// ====================
define('BASE_PATH', __DIR__ . '/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);
define('MAX_FILE_SIZE', 5242880); // 5MB
define('SALT', 'oikos-secure-salt-2026');

// ====================
// HELPER FUNCTIONS
// ====================

/**
 * Sanitize user input
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number
 */
function validate_phone($phone) {
    // Basic validation for Philippines numbers
    return preg_match('/^(\+63|0)[0-9]{9,10}$/', str_replace([' ', '-', '(', ')'], '', $phone));
}

/**
 * Log security events
 */
function log_security_event($event, $data = []) {
    $log_file = __DIR__ . '/../logs/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    
    $log_entry = "[$timestamp] Event: $event | IP: $ip_address | Data: " . json_encode($data) . " | UA: $user_agent\n";
    
    @file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ====================
// RATE LIMITING
// ====================
function check_rate_limit($key, $max_requests = 10, $time_window = 60) {
    $cache_file = __DIR__ . '/../logs/rate_limit_' . md5($key) . '.json';
    
    $requests = [];
    if (file_exists($cache_file)) {
        $requests = json_decode(file_get_contents($cache_file), true) ?? [];
    }
    
    $now = time();
    $requests = array_filter($requests, function($time) use ($now, $time_window) {
        return $time > ($now - $time_window);
    });
    
    if (count($requests) >= $max_requests) {
        return false;
    }
    
    $requests[] = $now;
    @file_put_contents($cache_file, json_encode($requests));
    
    return true;
}

// ====================
// LOG DIRECTORY CREATION
// ====================
$logs_dir = __DIR__ . '/../logs';
if (!is_dir($logs_dir)) {
    @mkdir($logs_dir, 0755, true);
}

?>
