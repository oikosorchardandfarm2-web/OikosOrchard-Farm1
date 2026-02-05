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
        
        // Create HTML email design
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #2d5016 0%, #4a7c2e 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0 0 0; font-size: 14px; opacity: 0.9; }
        .content { padding: 30px; }
        .content h2 { color: #2d5016; font-size: 18px; margin-top: 0; }
        .info-box { background-color: #f9f9f9; border-left: 4px solid #4a7c2e; padding: 15px; margin: 15px 0; }
        .info-row { margin: 12px 0; }
        .info-label { color: #666; font-weight: bold; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { color: #333; font-size: 14px; margin-top: 4px; word-break: break-word; }
        .message-box { background-color: #fffacd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        .timestamp { color: #999; font-size: 12px; text-align: right; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        .footer { background-color: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌾 Oikos Orchard & Farm</h1>
            <p>New Contact Form Submission</p>
        </div>
        
        <div class="content">
            <h2>Contact Details</h2>
            
            <div class="info-box">
                <div class="info-row">
                    <div class="info-label">Name</div>
                    <div class="info-value">$name</div>
                </div>
            </div>
            
            <div class="info-box">
                <div class="info-row">
                    <div class="info-label">Email</div>
                    <div class="info-value"><a href="mailto:$email" style="color: #4a7c2e; text-decoration: none;">$email</a></div>
                </div>
            </div>
            
            <div class="info-box">
                <div class="info-row">
                    <div class="info-label">Phone</div>
                    <div class="info-value">$phone</div>
                </div>
            </div>
            
            <h2>Message</h2>
            <div class="message-box">
                <div class="info-value">$body</div>
            </div>
            
            <div class="timestamp">
                Submitted: <strong>" . date('M d, Y | g:i A') . "</strong>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
            <p>This is an automated message from your contact form.</p>
        </div>
    </div>
</body>
</html>
HTML;
        
        $mail->isHTML = true;
        
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
