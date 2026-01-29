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

    // Admin email address
    $adminEmail = 'oikosorchardandfarm2@gmail.com';
    $siteEmail = 'oikosorchardandfarm2@gmail.com';

    // ====== SEND EMAIL TO ADMIN ======
    $adminSubject = 'New Get Started Request - Oikos Orchard & Farm';
    $adminMessage = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 8px; }
            .header { background: #27ae60; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
            .content { background: white; padding: 20px; }
            .field { margin: 15px 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
            .label { font-weight: bold; color: #27ae60; display: inline-block; width: 150px; }
            .value { display: inline-block; }
            .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>📋 New Get Started Request</h2>
            </div>
            <div class='content'>
                <div class='field'>
                    <span class='label'>Name:</span>
                    <span class='value'>{$name}</span>
                </div>
                <div class='field'>
                    <span class='label'>Email:</span>
                    <span class='value'>{$email}</span>
                </div>
                <div class='field'>
                    <span class='label'>Phone:</span>
                    <span class='value'>{$phone}</span>
                </div>
                <div class='field'>
                    <span class='label'>Interested In:</span>
                    <span class='value'>{$interested}</span>
                </div>
                <div class='field'>
                    <span class='label'>Submitted:</span>
                    <span class='value'>" . date('Y-m-d H:i:s') . "</span>
                </div>
            </div>
            <div class='footer'>
                <p>This is an automated notification from Oikos Orchard & Farm website.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Email headers for admin
    $adminHeaders = "MIME-Version: 1.0\r\n";
    $adminHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
    $adminHeaders .= "From: noreply@oikosorchardandfarm.com\r\n";
    $adminHeaders .= "Reply-To: {$email}\r\n";

    // Send email to admin
    $adminEmailSent = mail($adminEmail, $adminSubject, $adminMessage, $adminHeaders);

    // ====== SEND CONFIRMATION EMAIL TO USER ======
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
                <h2>🌱 Welcome to Oikos Orchard & Farm!</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>{$name}</strong>,</p>
                <p>Thank you for your interest in <strong>Oikos Orchard & Farm</strong>! We have received your request and will contact you shortly.</p>
                
                <h3>Your Request Details:</h3>
                <ul>
                    <li><strong>Interested In:</strong> {$interested}</li>
                    <li><strong>Submitted:</strong> " . date('Y-m-d H:i:s') . "</li>
                </ul>

                <p>Our team will reach out to you within <strong>24 hours</strong> to discuss your needs and how we can help you.</p>

                <h3>Contact Information:</h3>
                <ul>
                    <li>📧 <strong>Email:</strong> oikosorchardandfarm2@gmail.com</li>
                    <li>📱 <strong>Phone:</strong> +63 917 777 0851</li>
                    <li>📍 <strong>Address:</strong> Vegetable Highway, Upper Bae, Sibonga, Cebu, Philippines</li>
                </ul>

                <p>If you have any immediate questions, feel free to reach out to us directly.</p>

                <p>Best regards,<br>
                <strong>🌿 Oikos Orchard & Farm Team</strong></p>
            </div>
            <div class='footer'>
                <p>&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
                <p>Sustainable Agriculture | Organic Products | Agritourism</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Email headers for user
    $userHeaders = "MIME-Version: 1.0\r\n";
    $userHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
    $userHeaders .= "From: Oikos Orchard & Farm <{$siteEmail}>\r\n";

    // Send confirmation email to user
    $userEmailSent = mail($email, $userSubject, $userMessage, $userHeaders);

    // Log the request for records
    $logEntry = date('Y-m-d H:i:s') . " | Name: {$name} | Email: {$email} | Phone: {$phone} | Interested: {$interested}\n";
    file_put_contents(__DIR__ . '/getstarted-log.txt', $logEntry, FILE_APPEND);

    // Respond based on email results
    if ($adminEmailSent || $userEmailSent) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Thank you! We have received your request and will contact you shortly. Check your email for confirmation.'
        ]);
    } else {
        // Even if email fails, we logged it
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Your request has been received! We will contact you soon.'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again later.'
    ]);
}
?>
