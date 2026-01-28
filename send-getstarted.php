<?php
header('Content-Type: application/json; charset=utf-8');

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Get JSON data from request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data received']);
        exit;
    }

    // Validate required fields
    if (empty($input['name']) || empty($input['email']) || empty($input['phone']) || empty($input['interested'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit;
    }

    // Sanitize inputs
    $name = htmlspecialchars(trim($input['name']));
    $email = htmlspecialchars(trim($input['email']));
    $phone = htmlspecialchars(trim($input['phone']));
    $interested = htmlspecialchars(trim($input['interested']));

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }

    // Gmail account to receive notifications
    $gmailAddress = 'oikosorchardandfarm2@gmail.com';

    // Prepare email content for admin notification
    $adminSubject = 'New Get Started Request - Oikos Orchard & Farm';
    $adminMessage = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 8px; }
            .header { background: #27ae60; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
            .content { background: white; padding: 20px; }
            .field { margin: 15px 0; }
            .label { font-weight: bold; color: #27ae60; }
            .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Get Started Request</h2>
            </div>
            <div class='content'>
                <div class='field'>
                    <span class='label'>Name:</span> {$name}
                </div>
                <div class='field'>
                    <span class='label'>Email:</span> {$email}
                </div>
                <div class='field'>
                    <span class='label'>Phone:</span> {$phone}
                </div>
                <div class='field'>
                    <span class='label'>Interested In:</span> {$interested}
                </div>
                <div class='field'>
                    <span class='label'>Submitted:</span> " . date('Y-m-d H:i:s') . "
                </div>
            </div>
            <div class='footer'>
                <p>This is an automated notification from Oikos Orchard & Farm website.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Prepare email content for user confirmation
    $userSubject = 'Thank You for Getting Started - Oikos Orchard & Farm';
    $userMessage = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 8px; }
            .header { background: #27ae60; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
            .content { background: white; padding: 20px; line-height: 1.6; }
            .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Welcome to Oikos Orchard & Farm!</h2>
            </div>
            <div class='content'>
                <p>Dear {$name},</p>
                <p>Thank you for your interest in Oikos Orchard & Farm! We have received your request and will contact you shortly.</p>
                <p><strong>Your Information:</strong></p>
                <ul>
                    <li>Phone: {$phone}</li>
                    <li>Interested In: {$interested}</li>
                </ul>
                <p>Our team will reach out to you within 24 hours to discuss your needs and how we can help you.</p>
                <p>If you have any immediate questions, feel free to contact us at:</p>
                <ul>
                    <li>Email: oikosorchardandfarm2@gmail.com</li>
                    <li>Phone: +63 917 777 0851</li>
                </ul>
                <p>Best regards,<br><strong>Oikos Orchard & Farm Team</strong></p>
            </div>
            <div class='footer'>
                <p>&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Email headers
    $adminHeaders = "MIME-Version: 1.0" . "\r\n";
    $adminHeaders .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $adminHeaders .= "From: noreply@oikosorchardandfarm.com" . "\r\n";

    $userHeaders = "MIME-Version: 1.0" . "\r\n";
    $userHeaders .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $userHeaders .= "From: Oikos Orchard & Farm <oikosorchardandfarm2@gmail.com>" . "\r\n";

    // Send email to admin
    $adminEmailSent = mail($gmailAddress, $adminSubject, $adminMessage, $adminHeaders);
    
    // Send confirmation email to user
    $userEmailSent = mail($email, $userSubject, $userMessage, $userHeaders);

    if ($adminEmailSent && $userEmailSent) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Thank you! We have received your request and will contact you shortly.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error sending email. Please try again later.'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
