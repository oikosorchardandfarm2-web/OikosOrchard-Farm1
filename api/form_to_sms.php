<?php
/**
 * Contact Form Email Handler
 * Sends SMS notifications via email-to-SMS gateways using Gmail SMTP
 */

// Prevent any output before JSON
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Load configuration
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/gmail-config.php';
require_once __DIR__ . '/send-sms.php';

header('Content-Type: application/json; charset=utf-8');
ob_end_clean();

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Get form data
    $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '';
    $body = isset($_POST['body']) ? htmlspecialchars(trim($_POST['body'])) : '';

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone) || empty($body)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }

    // Validate message length
    if (strlen($body) > 160) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message exceeds 160 character limit']);
        exit;
    }

    // Log the contact form submission
    $logEntry = date('Y-m-d H:i:s') . " | Name: {$name} | Email: {$email} | Phone: {$phone} | Message: {$body}\n";
    $logResult = @file_put_contents(__DIR__ . '/contact-log.txt', $logEntry, FILE_APPEND);

    if (!$logResult) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error saving message. Please try again.']);
        exit;
    }

    // SMS notification details
    $ownerPhone = "09948962820";
    $phoneIntl = str_replace('0', '63', $ownerPhone);
    $smsSubject = "Oikos Contact";
    $smsBody = "New: $name | $body";
    
    $fromEmail = GMAIL_ADDRESS;
    $fromName = "Oikos Orchard & Farm";

    // Try to send SMS via all three carriers (DITO, Globe, Smart)
    $smsSent = false;
    
    // DITO (primary)
    if (sendEmailViaSMTP($phoneIntl . "@text.ditophone.com", $smsSubject, $smsBody, $fromEmail, $fromName, GMAIL_ADDRESS, GMAIL_APP_PASSWORD)) {
        $smsSent = true;
        error_log("SMS sent via DITO");
    }
    
    // Globe (backup)
    if (sendEmailViaSMTP($phoneIntl . "@mail.globelabs.com.ph", $smsSubject, $smsBody, $fromEmail, $fromName, GMAIL_ADDRESS, GMAIL_APP_PASSWORD)) {
        $smsSent = true;
        error_log("SMS sent via Globe");
    }
    
    // Smart (backup)
    if (sendEmailViaSMTP($phoneIntl . "@smspush.smart.com.ph", $smsSubject, $smsBody, $fromEmail, $fromName, GMAIL_ADDRESS, GMAIL_APP_PASSWORD)) {
        $smsSent = true;
        error_log("SMS sent via Smart");
    }
    
    // Also send to admin email
    $adminEmail = ADMIN_EMAIL;
    $adminSubject = "New Contact: $name";
    $adminBody = "New Contact Form Submission\n";
    $adminBody .= "============================\n\n";
    $adminBody .= "Name: " . $name . "\n";
    $adminBody .= "Email: " . $email . "\n";
    $adminBody .= "Phone: " . $phone . "\n";
    $adminBody .= "Message: " . $body . "\n\n";
    $adminBody .= "Submitted: " . date('Y-m-d H:i:s') . "\n";
    
    sendEmailViaSMTP($adminEmail, $adminSubject, $adminBody, $fromEmail, $fromName, GMAIL_ADDRESS, GMAIL_APP_PASSWORD);

    // Return success
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your message has been received. We will contact you soon.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Form Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again.'
    ]);
}
?>
