<?php
/**
 * Quick Email Test Script
 * Run this to verify PHPMailer and Gmail SMTP connection works
 * 
 * Usage: Visit http://localhost/OikosOrchardandFarm/test-email.php in browser
 */

// Enable error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/gmail-config.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Configuration Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .pass { background: #d4edda; border-color: #28a745; color: #155724; }
        .fail { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
        h1 { color: #333; }
    </style>
</head>
<body>

<h1>📧 Email Configuration Test</h1>

<div class="test info">
    <h3>Current Configuration</h3>
    <p><strong>Gmail Address:</strong> <code><?php echo GMAIL_ADDRESS; ?></code></p>
    <p><strong>SMTP Host:</strong> <code><?php echo SMTP_HOST; ?></code></p>
    <p><strong>SMTP Port:</strong> <code><?php echo SMTP_PORT; ?></code></p>
    <p><strong>SMTP Secure:</strong> <code><?php echo SMTP_SECURE; ?></code></p>
    <p><strong>Admin Email:</strong> <code><?php echo ADMIN_EMAIL; ?></code></p>
</div>

<?php

// Test 1: Check PHPMailer
echo '<div class="test">';
echo '<h3>✓ Test 1: PHPMailer Library</h3>';
try {
    $mail = new PHPMailer(true);
    echo '<p class="pass">✅ PHPMailer loaded successfully</p>';
} catch (Exception $e) {
    echo '<p class="fail">❌ PHPMailer failed: ' . $e->getMessage() . '</p>';
}
echo '</div>';

// Test 2: Check SMTP Connection
echo '<div class="test">';
echo '<h3>✓ Test 2: SMTP Connection</h3>';
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = SMTP_AUTH;
    $mail->Username = GMAIL_ADDRESS;
    $mail->Password = GMAIL_APP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port = SMTP_PORT;
    $mail->SMTPDebug = 0;
    
    // Test the connection
    if ($mail->smtpConnect()) {
        echo '<p class="pass">✅ SMTP connection successful!</p>';
        echo '<p>Connected to: ' . SMTP_HOST . ':' . SMTP_PORT . '</p>';
        $mail->smtpClose();
    } else {
        echo '<p class="fail">❌ SMTP connection failed</p>';
    }
} catch (Exception $e) {
    echo '<p class="fail">❌ SMTP Error: ' . $e->getMessage() . '</p>';
}
echo '</div>';

// Test 3: Send Test Email
echo '<div class="test">';
echo '<h3>✓ Test 3: Send Test Email</h3>';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    $testEmail = htmlspecialchars($_POST['test_email'] ?? '');
    
    if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        echo '<p class="fail">❌ Invalid email address provided</p>';
    } else {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = SMTP_AUTH;
            $mail->Username = GMAIL_ADDRESS;
            $mail->Password = GMAIL_APP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            
            $mail->setFrom(GMAIL_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($testEmail);
            $mail->isHTML(true);
            $mail->Subject = '📧 Test Email - Oikos Orchard & Farm';
            $mail->Body = '
            <html>
            <body style="font-family: Arial, sans-serif;">
                <h2>🌿 Email System Test</h2>
                <p>If you received this email, your Gmail SMTP configuration is working correctly!</p>
                <p><strong>Test Details:</strong></p>
                <ul>
                    <li>From: ' . GMAIL_ADDRESS . '</li>
                    <li>To: ' . $testEmail . '</li>
                    <li>Time: ' . date('Y-m-d H:i:s') . '</li>
                </ul>
                <p style="color: #27ae60; font-weight: bold;">✅ Your email system is ready!</p>
                <hr>
                <p style="font-size: 12px; color: #999;">
                    This is an automated test from Oikos Orchard & Farm
                </p>
            </body>
            </html>';
            
            if ($mail->send()) {
                echo '<p class="pass">✅ Test email sent successfully to <code>' . $testEmail . '</code></p>';
                echo '<p>Check your inbox (or spam folder) for the test email.</p>';
            } else {
                echo '<p class="fail">❌ Failed to send email</p>';
            }
        } catch (Exception $e) {
            echo '<p class="fail">❌ Email Error: ' . $e->getMessage() . '</p>';
        }
    }
} else {
    echo '
    <form method="POST">
        <p>Send a test email to verify the configuration:</p>
        <input type="email" name="test_email" placeholder="Enter your email address" required style="padding: 8px; width: 300px; border: 1px solid #ccc; border-radius: 3px;">
        <button type="submit" name="send_test" style="padding: 8px 20px; margin-left: 10px; background: #27ae60; color: white; border: none; border-radius: 3px; cursor: pointer;">
            Send Test Email
        </button>
    </form>
    ';
}

echo '</div>';

// Test 4: File Permissions
echo '<div class="test info">';
echo '<h3>✓ Test 4: File Permissions</h3>';

$files = [
    'send-email-helper.php',
    'gmail-config.php',
    'PHPMailer/PHPMailer.php',
    'PHPMailer/Exception.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo '<p class="pass">✅ <code>' . $file . '</code> - Found</p>';
    } else {
        echo '<p class="fail">❌ <code>' . $file . '</code> - NOT FOUND</p>';
    }
}

echo '</div>';

?>

<div class="test info">
    <h3>📝 Next Steps</h3>
    <ol>
        <li>Verify all tests pass above</li>
        <li>Send a test email using the form in Test 3</li>
        <li>Check your inbox for the test email</li>
        <li>If emails arrive, your booking and get-started forms should now work!</li>
        <li>If emails don't arrive, check the <a href="https://myaccount.google.com/security" target="_blank">Gmail Security Settings</a></li>
    </ol>
</div>

<div class="test" style="background: #fff3cd; border-color: #ffc107; color: #856404;">
    <h3>⚠️ Important Security Note</h3>
    <p>This test file is for development only. <strong>Delete this file</strong> before deploying to production to prevent exposing your email configuration.</p>
    <p>You can safely delete: <code>test-email.php</code></p>
</div>

</body>
</html>
