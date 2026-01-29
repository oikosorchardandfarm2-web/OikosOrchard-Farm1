<?php
// Email helper using PHPMailer for Gmail SMTP
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/gmail-config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email via Gmail SMTP
 * @param string $recipientEmail - Email address to send to
 * @param string $subject - Email subject
 * @param string $htmlBody - HTML email body
 * @param string $senderName - Name of sender (optional)
 * @return array - ['success' => bool, 'message' => string]
 */
function sendEmailViaGmail($recipientEmail, $subject, $htmlBody, $senderName = MAIL_FROM_NAME) {
    try {
        $mail = new PHPMailer(true);
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = SMTP_AUTH;
        $mail->Username   = GMAIL_ADDRESS;
        $mail->Password   = GMAIL_APP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        // Enable debug if needed (comment out in production)
        // $mail->SMTPDebug = 2;
        
        // Email details
        $mail->setFrom(GMAIL_ADDRESS, $senderName);
        $mail->addAddress($recipientEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        
        // Send email
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to send email: ' . $e->getMessage()
        ];
    }
}

/**
 * Send booking emails (to admin and customer)
 * @param string $customerEmail - Customer email address
 * @param string $fullName - Full name of customer
 * @param string $packageName - Package name
 * @param string $packagePrice - Package price
 * @param string $checkinDate - Check-in date
 * @param string $guests - Number of guests
 * @param string $specialRequests - Special requests
 * @param string $phone - Customer phone number
 * @param string $timestamp - Submission timestamp
 * @return void
 */
function sendBookingEmailsViaGmail($customerEmail, $fullName, $packageName, $packagePrice, $checkinDate, $guests, $specialRequests, $phone, $timestamp) {
    // Email to Admin
    $adminSubject = "New Booking Request - " . $packageName;
    $adminBody = getAdminEmailTemplate($fullName, $customerEmail, $phone, $packageName, $packagePrice, $checkinDate, $guests, $specialRequests, $timestamp);
    
    $adminResult = sendEmailViaGmail(ADMIN_EMAIL, $adminSubject, $adminBody);
    
    // Email to Customer
    $customerSubject = "✓ Booking Request Received - Oikos Orchard & Farm";
    $customerBody = getCustomerEmailTemplate($fullName, $packageName, $packagePrice, $checkinDate, $guests, $timestamp);
    
    $customerResult = sendEmailViaGmail($customerEmail, $customerSubject, $customerBody);
    
    // Log results
    if (!$adminResult['success'] || !$customerResult['success']) {
        error_log("Email sending issue - Admin: " . ($adminResult['success'] ? 'OK' : $adminResult['message']) . " | Customer: " . ($customerResult['success'] ? 'OK' : $customerResult['message']));
    }
}

/**
 * Send get-started inquiry emails
 * @param string $customerEmail - Customer email address
 * @param string $name - Name
 * @param string $phone - Phone number
 * @param string $interested - What they're interested in
 * @return void
 */
function sendGetStartedEmailsViaGmail($customerEmail, $name, $phone, $interested) {
    // Email to Admin
    $adminSubject = "New Get Started Request - " . $interested;
    $adminBody = getGetStartedAdminEmailTemplate($name, $customerEmail, $phone, $interested);
    
    $adminResult = sendEmailViaGmail(ADMIN_EMAIL, $adminSubject, $adminBody);
    
    // Email to Customer
    $customerSubject = "✓ Get Started Request Received - Oikos Orchard & Farm";
    $customerBody = getGetStartedCustomerEmailTemplate($name);
    
    $customerResult = sendEmailViaGmail($customerEmail, $customerSubject, $customerBody);
    
    // Log results
    if (!$adminResult['success'] || !$customerResult['success']) {
        error_log("Email sending issue - Admin: " . ($adminResult['success'] ? 'OK' : $adminResult['message']) . " | Customer: " . ($customerResult['success'] ? 'OK' : $customerResult['message']));
    }
}

// EMAIL TEMPLATES
function getAdminEmailTemplate($fullName, $email, $phone, $packageName, $packagePrice, $checkinDate, $guests, $specialRequests, $timestamp) {
    return "
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
                <div>$packagePrice</div>
            </div>
            <div class='field'>
                <div class='label'>👤 Guest Information</div>
                <div><strong>Name:</strong> $fullName<br><strong>Email:</strong> <a href='mailto:$email'>$email</a><br><strong>Phone:</strong> <a href='tel:$phone'>$phone</a></div>
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
            <p><strong>ACTION REQUIRED:</strong> Please respond to the customer at <a href='mailto:$email'>$email</a> within 24 hours to confirm their booking.</p>
            <p>&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
        </div>
    </div>
    </body></html>";
}

function getCustomerEmailTemplate($fullName, $packageName, $packagePrice, $checkinDate, $guests, $timestamp) {
    $gmailAddress = GMAIL_ADDRESS;
    return "
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
                    <strong>💰 Price:</strong> $packagePrice<br>
                    <strong>📅 Check-in Date:</strong> $checkinDate<br>
                    <strong>👥 Number of Guests:</strong> $guests<br>
                    <strong>⏰ Submitted:</strong> $timestamp
                </p>
            </div>
            
            <p><strong>What Happens Next?</strong></p>
            <p>Our team will review your booking request and <strong>contact you within 24 hours</strong> to:</p>
            <ul>
                <li>Confirm your reservation</li>
                <li>Provide payment details</li>
                <li>Answer any questions</li>
                <li>Share pre-arrival information</li>
            </ul>
            
            <p><strong>Need Immediate Assistance?</strong></p>
            <p>
                📞 <strong>Phone:</strong> +63 917 777 0851<br>
                📧 <strong>Email:</strong> $gmailAddress<br>
                📍 <strong>Location:</strong> Vegetable Highway, Upper Bae, Sibonga, Cebu, Philippines
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
}

function getGetStartedAdminEmailTemplate($name, $email, $phone, $interested) {
    return "
    <html><head><style>
    body { font-family: Arial, sans-serif; color: #333; }
    .container { max-width: 600px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 8px; }
    .header { background: #27ae60; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
    .content { background: white; padding: 20px; }
    .field { margin: 15px 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    .label { font-weight: bold; color: #27ae60; display: inline-block; width: 150px; }
    .value { display: inline-block; }
    .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
    </style></head><body>
    <div class='container'>
        <div class='header'>
            <h2>📋 New Get Started Request</h2>
        </div>
        <div class='content'>
            <div class='field'>
                <span class='label'>Name:</span>
                <span class='value'>$name</span>
            </div>
            <div class='field'>
                <span class='label'>Email:</span>
                <span class='value'><a href='mailto:$email'>$email</a></span>
            </div>
            <div class='field'>
                <span class='label'>Phone:</span>
                <span class='value'><a href='tel:$phone'>$phone</a></span>
            </div>
            <div class='field'>
                <span class='label'>Interested In:</span>
                <span class='value'>$interested</span>
            </div>
        </div>
        <div class='footer'>
            <p><strong>ACTION REQUIRED:</strong> Please reach out to $name at $email or $phone within 24 hours.</p>
            <p>&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
        </div>
    </div>
    </body></html>";
}

function getGetStartedCustomerEmailTemplate($name) {
    $gmailAddress = GMAIL_ADDRESS;
    return "
    <html><head><style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; border-radius: 8px; }
    .header { background: #27ae60; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
    .content { background: white; padding: 20px; }
    .footer { background: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 8px 8px; }
    </style></head><body>
    <div class='container'>
        <div class='header'>
            <h2>🌿 Oikos Orchard & Farm</h2>
            <p>Get Started Request Received</p>
        </div>
        <div class='content'>
            <p>Hi <strong>$name</strong>,</p>
            
            <p>Thank you for your interest in <strong>Oikos Orchard & Farm</strong>! We're excited to hear from you.</p>
            
            <p>We have received your get started request and our team will reach out to you within 24 hours with more information and to discuss your needs.</p>
            
            <p><strong>Contact Information:</strong></p>
            <p>
                📞 <strong>Phone:</strong> +63 917 777 0851<br>
                📧 <strong>Email:</strong> $gmailAddress<br>
                📍 <strong>Location:</strong> Vegetable Highway, Upper Bae, Sibonga, Cebu, Philippines
            </p>
            
            <p>We look forward to connecting with you soon!</p>
            
            <p>Best regards,<br>
            <strong>🌿 The Oikos Orchard & Farm Team</strong></p>
        </div>
        <div class='footer'>
            <p>&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
        </div>
    </div>
    </body></html>";
}

?>
