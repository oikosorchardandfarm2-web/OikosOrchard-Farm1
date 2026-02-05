<?php
/**
 * Contact Form Email Handler
 * Sends SMS notifications via Email Gateway + SMTP
 */

// Prevent any output before JSON
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Load configuration
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/gmail-config.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';

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

    // Initialize PHPMailer for sending via Gmail SMTP
    $mail = new PHPMailer(true);
    
    try {
        // SMTP settings for Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 587;
        $mail->SMTPSecure = 'tls';
        $mail->SMTPAuth = true;
        $mail->Username = GMAIL_ADDRESS;
        $mail->Password = GMAIL_APP_PASSWORD;
        $mail->setFrom(GMAIL_ADDRESS, 'Oikos Orchard & Farm');

        // Send email to admin only
        $mail->addAddress(ADMIN_EMAIL);
        $mail->Subject = "New Contact: $name";
        $mail->Body = "New Contact Form Submission\n";
        $mail->Body .= "============================\n\n";
        $mail->Body .= "Name: " . $name . "\n";
        $mail->Body .= "Email: " . $email . "\n";
        $mail->Body .= "Phone: " . $phone . "\n";
        $mail->Body .= "Message: " . $body . "\n\n";
        $mail->Body .= "Submitted: " . date('Y-m-d H:i:s') . "\n";
        $mail->isHTML = false;
        
        try {
            $mail->send();
            error_log("Admin email sent successfully");
        } catch (Exception $e) {
            error_log("Admin email failed: " . $e->getMessage());
        }

    } catch (Exception $e) {
        // PHPMailer Exception
        http_response_code(500);
        error_log("Mailer Exception: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Server error. Please try again.'
        ]);
        exit;
    }

    // Return success
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your message has been received. We will contact you soon.'
    ]);

} catch (Exception $e) {
    // General Exception
    http_response_code(500);
    error_log("Form Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again.'
    ]);
}
?>
