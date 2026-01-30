<?php
/**
 * Contact Form to SMS Handler
 * Sends contact form messages via SMS to admin
 */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Get form data
    $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
    $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
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

    // Validate message length (SMS max 160 characters)
    if (strlen($body) > 160) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message exceeds 160 character limit']);
        exit;
    }

    // Load Twilio configuration
    require_once __DIR__ . '/../config/twilio-config.php';

    // Log the contact form submission
    $logEntry = date('Y-m-d H:i:s') . " | Name: {$name} | Email: {$email} | Phone: {$phone} | Message: {$body}\n";
    @file_put_contents(__DIR__ . '/contact-log.txt', $logEntry, FILE_APPEND);

    // Format SMS message
    $smsMessage = "📧 New Contact from Oikos Website:\n";
    $smsMessage .= "Name: $name\n";
    $smsMessage .= "Email: $email\n";
    $smsMessage .= "Phone: $phone\n";
    $smsMessage .= "Message: $body";

    // Send SMS to admin using Twilio
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_ACCOUNT_SID . "/Messages.json",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => http_build_query([
            'MessagingServiceSid' => TWILIO_MESSAGING_SERVICE_SID,
            'To' => NOTIFY_PHONE_NUMBER,
            'Body' => $smsMessage
        ]),
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => TWILIO_ACCOUNT_SID . ":" . TWILIO_AUTH_TOKEN,
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error sending message. Please try again.'
        ]);
        exit;
    }

    $responseData = json_decode($response, true);

    // Check if message was sent successfully
    if (isset($responseData['sid'])) {
        // Also send email backup notification to admin
        $adminEmail = 'oikosorchardandfarm2@gmail.com';
        $emailSubject = "New Contact Form Submission - Oikos Orchard & Farm";
        $emailBody = "New contact form submission:\n\n";
        $emailBody .= "Name: $name\n";
        $emailBody .= "Email: $email\n";
        $emailBody .= "Phone: $phone\n";
        $emailBody .= "Message: $body\n\n";
        $emailBody .= "Submitted: " . date('Y-m-d H:i:s') . "\n";
        
        $emailHeaders = "From: " . $email . "\r\n";
        $emailHeaders .= "Content-Type: text/plain; charset=utf-8\r\n";
        
        @mail($adminEmail, $emailSubject, $emailBody, $emailHeaders);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Thank you! Your message has been sent. We will contact you soon.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error sending message. Please try again.'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

ob_end_clean();
?>
