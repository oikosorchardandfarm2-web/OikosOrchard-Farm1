<?php
// Simple Booking Handler
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

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
    if (empty($input['fullName']) || empty($input['email']) || empty($input['phone']) || 
        empty($input['checkinDate']) || empty($input['guests']) || empty($input['packageName'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit;
    }

    // Sanitize inputs
    $fullName = htmlspecialchars(trim($input['fullName']));
    $email = htmlspecialchars(trim($input['email']));
    $phone = htmlspecialchars(trim($input['phone']));
    $checkinDate = htmlspecialchars(trim($input['checkinDate']));
    $guests = htmlspecialchars(trim($input['guests']));
    $packageName = htmlspecialchars(trim($input['packageName']));
    $packagePrice = htmlspecialchars(trim($input['packagePrice'] ?? ''));
    $specialRequests = htmlspecialchars(trim($input['specialRequests'] ?? ''));

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }

    // Gmail account
    $gmailAddress = ADMIN_EMAIL;
    $timestamp = date('Y-m-d H:i:s');
    
    // Create booking data array
    $bookingData = [
        'fullName' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'checkinDate' => $checkinDate,
        'guests' => $guests,
        'packageName' => $packageName,
        'packagePrice' => $packagePrice,
        'specialRequests' => $specialRequests,
        'timestamp' => $timestamp,
        'id' => uniqid('booking_')
    ];
    
    // Save to bookings.json
    $bookingsFile = __DIR__ . '/bookings.json';
    $bookings = file_exists($bookingsFile) ? json_decode(file_get_contents($bookingsFile), true) ?? [] : [];
    $bookings[] = $bookingData;
    @file_put_contents($bookingsFile, json_encode($bookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    // Send to Google Sheets (non-blocking - don't fail if it has issues)
    try {
        $sheetId = '1pWE72focDg7ZguylUJIaSysHZg1qxfQ_JiiT4Fk-26c';
        $googleSheetUrl = 'https://script.google.com/macros/s/AKfycby_SVLSpAVC7S9JCbtpoVowpoJX4TWBdeOtvEj1elO3TuxReanmEAAavGaO8ShjlEcu1Q/exec';
        
        $payload = json_encode([
            'sheetId' => $sheetId,
            'bookingId' => $bookingData['id'],
            'fullName' => $bookingData['fullName'],
            'email' => $bookingData['email'],
            'phone' => $bookingData['phone'],
            'checkinDate' => $bookingData['checkinDate'],
            'guests' => $bookingData['guests'],
            'packageName' => $bookingData['packageName'],
            'packagePrice' => $bookingData['packagePrice'],
            'specialRequests' => $bookingData['specialRequests'],
            'timestamp' => $bookingData['timestamp']
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $googleSheetUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        error_log("Google Sheets response: HTTP $httpCode - $response");
    } catch (Exception $e) {
        error_log("Google Sheets error: " . $e->getMessage());
    }
    
    // Send simple email notification
    try {
        $subject = "New Booking Request - " . $packageName;
        $body = "New Booking:\n\nName: $fullName\nEmail: $email\nPhone: $phone\nPackage: $packageName\nCheck-in: $checkinDate\nGuests: $guests\n\nSubmitted: $timestamp";
        
        @mail($gmailAddress, $subject, $body, "From: $email\r\nContent-Type: text/plain");
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
    }
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Booking submitted successfully! Our team will contact you within 24 hours.',
        'data' => $bookingData
    ]);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}

?>

// Old functions have been removed to use PHPMailer via Gmail SMTP

?>

    // ============ EMAIL TO ADMIN ============
    $adminSubject = "New Booking Request - " . $packageName;
    $adminBody = "
    <html><head><style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; border-radius: 8px; }
    .header { background: linear-gradient(135deg, #4A3728 0%, #2F251A 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
    .content { background: white; padding: 20px; }
    .field { margin: 15px 0; padding: 10px; border-left: 4px solid #27ae60; }
    .label { font-weight: bold; color: #27ae60; }
    .badge { display: inline-block; background: #27ae60; color: white; padding: 5px 10px; border-radius: 3px; font-weight: bold; }
    .footer { background: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 8px 8px; }
    </style></head><body>
    <div class='container'>
        <div class='header'>
            <h2>🌿 Oikos Orchard & Farm</h2>
            <p>New Booking Request Received</p>
        </div>
        <div class='content'>
            <div class='field'>
                <div class='label'>📦 Package</div>
                <div><span class='badge'>$packageName</span></div>
            </div>
            <div class='field'>
                <div class='label'>💰 Price</div>
                <div>₱$packagePrice</div>
            </div>
            <div class='field'>
                <div class='label'>👤 Guest Information</div>
                <div><strong>Name:</strong> $fullName<br><strong>Email:</strong> <a href='mailto:$customerEmail'>$customerEmail</a><br><strong>Phone:</strong> <a href='tel:$phone'>$phone</a></div>
            </div>
            <div class='field'>
                <div class='label'>📅 Booking Details</div>
                <div><strong>Check-in Date:</strong> $checkinDate<br><strong>Number of Guests:</strong> $guests</div>
            </div>
            " . (!empty($specialRequests) ? "<div class='field'><div class='label'>📝 Special Requests</div><div>$specialRequests</div></div>" : "") . "
            <div class='field'>
                <div class='label'>⏰ Submitted At</div>
                <div>$timestamp</div>
            </div>
        </div>
        <div class='footer'>
            <p><strong>ACTION REQUIRED:</strong> Please respond to the customer at <a href='mailto:$customerEmail'>$customerEmail</a> within 24 hours to confirm their booking.</p>
            <p>&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
        </div>
    </div>
    </body></html>";
    
    $adminHeaders = "MIME-Version: 1.0\r\n";
    $adminHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
    $adminHeaders .= "From: Website Booking System <noreply@oikosorchardandfarm.com>\r\n";
    $adminHeaders .= "Reply-To: $customerEmail\r\n";
    
    @mail($gmailAddress, $adminSubject, $adminBody, $adminHeaders);
    
    // ============ EMAIL TO CUSTOMER ============
    $customerSubject = "✓ Booking Request Received - Oikos Orchard & Farm";
    $customerBody = "
    <html><head><style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; border-radius: 8px; }
    .header { background: linear-gradient(135deg, #4A3728 0%, #2F251A 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
    .content { background: white; padding: 20px; line-height: 1.8; }
    .info-box { background: #f0f9f4; padding: 15px; border-left: 4px solid #27ae60; margin: 20px 0; border-radius: 3px; }
    .footer { background: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 8px 8px; }
    .success { color: #27ae60; font-weight: bold; }
    </style></head><body>
    <div class='container'>
        <div class='header'>
            <h2>🌿 Oikos Orchard & Farm</h2>
            <p>Your Booking Request is Confirmed</p>
        </div>
        <div class='content'>
            <p>Dear <strong>$fullName</strong>,</p>
            
            <p>Thank you for choosing <strong>Oikos Orchard & Farm</strong> for your glamping experience! We are thrilled to welcome you.</p>
            
            <div class='info-box'>
                <p><span class='success'>✓ Your Booking Details:</span></p>
                <p>
                    <strong>📦 Package:</strong> $packageName<br>
                    <strong>💰 Price:</strong> ₱$packagePrice<br>
                    <strong>📅 Check-in Date:</strong> $checkinDate<br>
                    <strong>👥 Number of Guests:</strong> $guests<br>
                    <strong>⏰ Submitted:</strong> $timestamp
                </p>
            </div>
            
            <p><strong>What Happens Next?</strong></p>
            <p>Our team will review your booking request and <strong>contact you within 24 hours</strong> at <strong>$phone</strong> to:</p>
            <ul>
                <li>Confirm your reservation</li>
                <li>Provide payment details</li>
                <li>Answer any questions</li>
                <li>Share pre-arrival information</li>
            </ul>
            
            <p><strong>Need Immediate Assistance?</strong></p>
            <p>
                📞 <strong>Phone:</strong> +1 (555) 123-4567<br>
                📧 <strong>Email:</strong> oikosorchardandfarm@example.com<br>
                🌐 <strong>Website:</strong> www.oikosorchardandfarm.com<br>
                📍 <strong>Location:</strong> Oikos Orchard & Farm, Valley Region
            </p>
            
            <p>We look forward to hosting an unforgettable experience for you and your group!</p>
            
            <p>Best regards,<br>
            <strong>🌿 The Oikos Orchard & Farm Team</strong></p>
        </div>
        <div class='footer'>
            <p>&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
            <p><em>This is an automated confirmation email. Please do not reply to this email directly.</em></p>
        </div>
    </div>
    </body></html>";
    
    $customerHeaders = "MIME-Version: 1.0\r\n";
    $customerHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
    $customerHeaders .= "From: Oikos Orchard & Farm <" . ADMIN_EMAIL . ">\r\n";
    $customerHeaders .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
    
    @mail($customerEmail, $customerSubject, $customerBody, $customerHeaders);
}
?>
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
    if (empty($input['fullName']) || empty($input['email']) || empty($input['phone']) || empty($input['checkinDate']) || empty($input['guests']) || empty($input['packageName'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit;
    }

    // Sanitize inputs
    $fullName = htmlspecialchars(trim($input['fullName']));
    $email = htmlspecialchars(trim($input['email']));
    $phone = htmlspecialchars(trim($input['phone']));
    $checkinDate = htmlspecialchars(trim($input['checkinDate']));
    $guests = htmlspecialchars(trim($input['guests']));
    $packageName = htmlspecialchars(trim($input['packageName']));
    $packagePrice = htmlspecialchars(trim($input['packagePrice'] ?? ''));
    $specialRequests = htmlspecialchars(trim($input['specialRequests'] ?? ''));

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }

    // Gmail address
    $gmailAddress = ADMIN_EMAIL;
    
    // Prepare email content
    $timestamp = date('Y-m-d H:i:s');
    
    $emailSubject = "New Booking Request - " . $packageName;
    
    $emailBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; border-radius: 5px; }
            .header { background: linear-gradient(135deg, #4A3728 0%, #2F251A 100%); color: white; padding: 20px; border-radius: 5px 5px 0 0; text-align: center; }
            .content { background: white; padding: 20px; }
            .field { margin: 15px 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
            .label { font-weight: bold; color: #27ae60; }
            .value { margin-top: 5px; }
            .footer { background: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            .badge { display: inline-block; background: #27ae60; color: white; padding: 5px 10px; border-radius: 3px; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🌿 Oikos Orchard & Farm</h2>
                <p>New Booking Request Received</p>
            </div>
            
            <div class='content'>
                <div class='field'>
                    <div class='label'>Package</div>
                    <div class='value'><span class='badge'>$packageName</span></div>
                </div>
                
                <div class='field'>
                    <div class='label'>Price</div>
                    <div class='value'>$packagePrice</div>
                </div>
                
                <div class='field'>
                    <div class='label'>Guest Information</div>
                    <div class='value'>
                        <strong>Name:</strong> $fullName<br>
                        <strong>Email:</strong> <a href='mailto:$email'>$email</a><br>
                        <strong>Phone:</strong> <a href='tel:$phone'>$phone</a>
                    </div>
                </div>
                
                <div class='field'>
                    <div class='label'>Booking Details</div>
                    <div class='value'>
                        <strong>Check-in Date:</strong> $checkinDate<br>
                        <strong>Number of Guests:</strong> $guests
                    </div>
                </div>
                
                " . (!empty($specialRequests) ? "
                <div class='field'>
                    <div class='label'>Special Requests</div>
                    <div class='value'>$specialRequests</div>
                </div>
                " : "") . "
                
                <div class='field'>
                    <div class='label'>Submission Time</div>
                    <div class='value'>$timestamp</div>
                </div>
            </div>
            
            <div class='footer'>
                <p>This is an automated booking request from your website.</p>
                <p>Please respond to the customer at $email within 24 hours.</p>
                <p>&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $email . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    
    // Booking data array
    $bookingData = array(
        'fullName' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'checkinDate' => $checkinDate,
        'guests' => $guests,
        'packageName' => $packageName,
        'packagePrice' => $packagePrice,
        'specialRequests' => $specialRequests,
        'timestamp' => $timestamp
    );
    
    // Try to send email using mail() function
    $mailSent = @mail($gmailAddress, $emailSubject, $emailBody, $headers);
    
    // Send confirmation email to customer
    $customerSubject = "Booking Confirmation - Oikos Orchard & Farm";
    $customerBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; border-radius: 5px; }
            .header { background: linear-gradient(135deg, #4A3728 0%, #2F251A 100%); color: white; padding: 20px; border-radius: 5px 5px 0 0; text-align: center; }
            .content { background: white; padding: 20px; }
            .footer { background: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            .success { color: #27ae60; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🌿 Oikos Orchard & Farm</h2>
                <p>Booking Request Received</p>
            </div>
            
            <div class='content'>
                <p>Dear <strong>$fullName</strong>,</p>
                
                <p>Thank you for your booking request for <strong>$packageName</strong>!</p>
                
                <p>We have received your request and will contact you shortly at <strong>$phone</strong> to confirm your reservation.</p>
                
                <div style='background: #f0f9f4; padding: 15px; border-left: 4px solid #27ae60; margin: 20px 0;'>
                    <p><span class='success'>Booking Details:</span></p>
                    <p>
                        <strong>Package:</strong> $packageName<br>
                        <strong>Price:</strong> $packagePrice<br>
                        <strong>Check-in Date:</strong> $checkinDate<br>
                        <strong>Guests:</strong> $guests
                    </p>
                </div>
                
                <p>Our team will reach out to you within 24 hours to finalize your booking and answer any questions you may have.</p>
                
                <p>If you have any urgent inquiries, please don't hesitate to contact us directly at:</p>
                <p>
                    📞 Phone: +1 (555) 123-4567<br>
                    📧 Email: oikosorchardandfarm@example.com
                </p>
                
                <p>Best regards,<br>
                <strong>Oikos Orchard & Farm Team</strong></p>
            </div>
            
            <div class='footer'>
                <p>&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $customerHeaders = "MIME-Version: 1.0\r\n";
    $customerHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
    $customerHeaders .= "From: Oikos Orchard & Farm <" . $gmailAddress . ">\r\n";
    
    @mail($email, $customerSubject, $customerBody, $customerHeaders);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Booking request submitted successfully! Check your email for confirmation.',
        'data' => $bookingData
    ]);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
    
    $emailBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; border-radius: 5px; }
            .header { background: linear-gradient(135deg, #4A3728 0%, #2F251A 100%); color: white; padding: 20px; border-radius: 5px 5px 0 0; text-align: center; }
            .content { background: white; padding: 20px; }
            .field { margin: 15px 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
            .label { font-weight: bold; color: #27ae60; }
            .value { margin-top: 5px; }
            .footer { background: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            .badge { display: inline-block; background: #27ae60; color: white; padding: 5px 10px; border-radius: 3px; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🌿 Oikos Orchard & Farm</h2>
                <p>New Booking Request Received</p>
            </div>
            
            <div class='content'>
                <div class='field'>
                    <div class='label'>Package</div>
                    <div class='value'><span class='badge'>$packageName</span></div>
                </div>
                
                <div class='field'>
                    <div class='label'>Price</div>
                    <div class='value'>$packagePrice</div>
                </div>
                
                <div class='field'>
                    <div class='label'>Guest Information</div>
                    <div class='value'>
                        <strong>Name:</strong> $fullName<br>
                        <strong>Email:</strong> <a href='mailto:$email'>$email</a><br>
                        <strong>Phone:</strong> <a href='tel:$phone'>$phone</a>
                    </div>
                </div>
                
                <div class='field'>
                    <div class='label'>Booking Details</div>
                    <div class='value'>
                        <strong>Check-in Date:</strong> $checkinDate<br>
                        <strong>Number of Guests:</strong> $guests
                    </div>
                </div>
                
                " . (!empty($specialRequests) ? "
                <div class='field'>
                    <div class='label'>Special Requests</div>
                    <div class='value'>$specialRequests</div>
                </div>
                " : "") . "
                
                <div class='field'>
                    <div class='label'>Submission Time</div>
                    <div class='value'>$timestamp</div>
                </div>
            </div>
            
            <div class='footer'>
                <p>This is an automated booking request from your website.</p>
                <p>Please respond to the customer at $email within 24 hours.</p>
                <p>&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $email . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    
    // Send email to Gmail using mail() function
    // Note: This requires your server to be configured with a mail relay
    // For production, use PHPMailer with SMTP
    
    // Using a more reliable approach - store and process
    $bookingData = array(
        'fullName' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'checkinDate' => $checkinDate,
        'guests' => $guests,
        'packageName' => $packageName,
        'packagePrice' => $packagePrice,
        'specialRequests' => $specialRequests,
        'timestamp' => $timestamp,
        'submittedAt' => date('Y-m-d H:i:s')
    );
    
    // Try to send email using mail() function
    $mailSent = mail($gmailAddress, $emailSubject, $emailBody, $headers);
    
    if ($mailSent) {
        // Also send confirmation email to customer
        $customerSubject = "Booking Confirmation - Oikos Orchard & Farm";
        $customerBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; border-radius: 5px; }
                .header { background: linear-gradient(135deg, #4A3728 0%, #2F251A 100%); color: white; padding: 20px; border-radius: 5px 5px 0 0; text-align: center; }
                .content { background: white; padding: 20px; }
                .footer { background: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                .success { color: #27ae60; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🌿 Oikos Orchard & Farm</h2>
                    <p>Booking Request Received</p>
                </div>
                
                <div class='content'>
                    <p>Dear <strong>$fullName</strong>,</p>
                    
                    <p>Thank you for your booking request for <strong>$packageName</strong>!</p>
                    
                    <p>We have received your request and will contact you shortly at <strong>$phone</strong> to confirm your reservation.</p>
                    
                    <div style='background: #f0f9f4; padding: 15px; border-left: 4px solid #27ae60; margin: 20px 0;'>
                        <p><span class='success'>Booking Details:</span></p>
                        <p>
                            <strong>Package:</strong> $packageName<br>
                            <strong>Price:</strong> $packagePrice<br>
                            <strong>Check-in Date:</strong> $checkinDate<br>
                            <strong>Guests:</strong> $guests
                        </p>
                    </div>
                    
                    <p>Our team will reach out to you within 24 hours to finalize your booking and answer any questions you may have.</p>
                    
                    <p>If you have any urgent inquiries, please don't hesitate to contact us directly at:</p>
                    <p>
                        📞 Phone: +1 (555) 123-4567<br>
                        📧 Email: oikosorchardandfarm@example.com
                    </p>
                    
                    <p>Best regards,<br>
                    <strong>Oikos Orchard & Farm Team</strong></p>
                </div>
                
                <div class='footer'>
                    <p>&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $customerHeaders = "MIME-Version: 1.0\r\n";
        $customerHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
        $customerHeaders .= "From: Oikos Orchard & Farm <" . $gmailAddress . ">\r\n";
        
        mail($email, $customerSubject, $customerBody, $customerHeaders);
        
        echo json_encode([
            'success' => true,
            'message' => 'Booking request submitted successfully! We will contact you soon.',
            'data' => $bookingData
        ]);
    } else {
        // If mail() fails, try alternative approach
        echo json_encode([
            'success' => true,
            'message' => 'Booking request received! We will contact you shortly.',
            'data' => $bookingData
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error processing booking request. Please try again.',
        'error' => $e->getMessage()
    ]);
}
?>
