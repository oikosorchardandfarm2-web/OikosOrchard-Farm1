<?php
// Simple Booking Handler - Optimized for Fast Response
require_once __DIR__ . '/../config/security.php';

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

    // Admin email and timestamp
    $adminEmail = ADMIN_EMAIL;
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
    $bookingsFile = __DIR__ . '/../bookings.json';
    $bookings = file_exists($bookingsFile) ? json_decode(file_get_contents($bookingsFile), true) ?? [] : [];
    $bookings[] = $bookingData;
    @file_put_contents($bookingsFile, json_encode($bookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    // Send immediate success response to user
    http_response_code(200);
    $successResponse = json_encode([
        'success' => true,
        'message' => 'Booking submitted successfully! Check your email for confirmation.',
        'data' => $bookingData
    ]);
    
    // Send response immediately and close connection to allow background processing
    header('Content-Length: ' . strlen($successResponse));
    echo $successResponse;
    flush();
    
    // Allow script to continue running in background after response is sent
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        // For non-FPM environments
        ob_end_flush();
        flush();
    }
    
    // ============ BACKGROUND PROCESSING - User won't wait for this ============
    
    // Send to Google Sheets (non-blocking - don't fail if it has issues)
    try {
        $sheetId = '1pWE72focDg7ZguylUJIaSysHZg1qxfQ_JiiT4Fk-26c';
        $googleSheetUrl = 'https://script.google.com/macros/s/AKfycbwgTqy_jJGV02649VuPAi96dzaHOtwlc6gGmiWMxCYNe7I8jTE17b18C5qIt1etGRuXiw/exec';
        
        // Ensure phone is sent as string with leading apostrophe for Google Sheets
        $phoneForSheet = "'" . $bookingData['phone'];
        
        $payload = json_encode([
            'sheetId' => $sheetId,
            'bookingId' => $bookingData['id'],
            'fullName' => $bookingData['fullName'],
            'email' => $bookingData['email'],
            'phone' => $phoneForSheet,
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        error_log("Google Sheets response: HTTP $httpCode - " . substr($response, 0, 500));
    } catch (Exception $e) {
        error_log("Google Sheets error: " . $e->getMessage());
    }
    
    // Send admin notification email
    $adminSubject = "New Booking Request - " . $packageName;
    $adminBody = "
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
                <h2>ðŸŒ¿ Oikos Orchard & Farm</h2>
                <p>New Booking Request Received</p>
            </div>
            
            <div class='content'>
                <div class='field'>
                    <div class='label'>Package</div>
                    <div class='value'><span class='badge'>$packageName</span></div>
                </div>
                
                <div class='field'>
                    <div class='label'>Price</div>
                    <div class='value'>â‚±$packagePrice</div>
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
                <p>Please respond to the customer at <a href='mailto:$email'>$email</a> within 24 hours.</p>
                <p>&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $adminHeaders = "MIME-Version: 1.0\r\n";
    $adminHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
    $adminHeaders .= "From: Website Booking System <noreply@oikosorchardandfarm.com>\r\n";
    $adminHeaders .= "Reply-To: $email\r\n";
    
    @mail($adminEmail, $adminSubject, $adminBody, $adminHeaders);
    
    // Send customer confirmation email
    $customerSubject = "âœ“ Booking Request Received - Oikos Orchard & Farm";
    $customerBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; border-radius: 5px; }
            .header { background: linear-gradient(135deg, #4A3728 0%, #2F251A 100%); color: white; padding: 20px; border-radius: 5px 5px 0 0; text-align: center; }
            .content { background: white; padding: 20px; line-height: 1.8; }
            .info-box { background: #f0f9f4; padding: 15px; border-left: 4px solid #27ae60; margin: 20px 0; border-radius: 3px; }
            .footer { background: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 5px 5px; }
            .success { color: #27ae60; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>ðŸŒ¿ Oikos Orchard & Farm</h2>
                <p>Your Booking Request is Confirmed</p>
            </div>
            
            <div class='content'>
                <p>Dear <strong>$fullName</strong>,</p>
                
                <p>Thank you for choosing <strong>Oikos Orchard & Farm</strong> for your glamping experience! We are thrilled to welcome you.</p>
                
                <div class='info-box'>
                    <p><span class='success'>âœ“ Your Booking Details:</span></p>
                    <p>
                        <strong>ðŸ“¦ Package:</strong> $packageName<br>
                        <strong>ðŸ’° Price:</strong> â‚±$packagePrice<br>
                        <strong>ðŸ“… Check-in Date:</strong> $checkinDate<br>
                        <strong>ðŸ‘¥ Number of Guests:</strong> $guests<br>
                        <strong>â° Submitted:</strong> $timestamp
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
                    ðŸ“ž <strong>Phone:</strong> +63 (2) 1234 5678<br>
                    ðŸ“§ <strong>Email:</strong> contact@oikosorchardandfarm.com<br>
                    ðŸŒ <strong>Website:</strong> www.oikosorchardandfarm.com
                </p>
                
                <p>We look forward to hosting an unforgettable experience for you and your group!</p>
                
                <p>Best regards,<br>
                <strong>ðŸŒ¿ The Oikos Orchard & Farm Team</strong></p>
            </div>
            
            <div class='footer'>
                <p>&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
                <p><em>This is an automated confirmation email. Please do not reply to this email directly.</em></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $customerHeaders = "MIME-Version: 1.0\r\n";
    $customerHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
    $customerHeaders .= "From: Oikos Orchard & Farm <" . $adminEmail . ">\r\n";
    $customerHeaders .= "Reply-To: " . $adminEmail . "\r\n";
    
    @mail($email, $customerSubject, $customerBody, $customerHeaders);
    
    // Background processing complete
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    error_log("Booking error: " . $e->getMessage());
    exit;
}
?>
