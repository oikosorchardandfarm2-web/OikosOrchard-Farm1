<?php
// Start output buffering to prevent accidental output before JSON
ob_start();

// Set strict error reporting but don't display to user
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// Include SMS helper with Twilio
require_once __DIR__ . '/send-sms-helper.php';

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_end_clean();
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Get JSON data from request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data received']);
        exit;
    }

    // Validate required fields
    if (empty($input['name']) || empty($input['email']) || empty($input['phone']) || empty($input['interested'])) {
        ob_end_clean();
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
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }

    // Admin email address
    $adminEmail = 'oikosorchardandfarm2@gmail.com';

    // Log the request for records
    $logEntry = date('Y-m-d H:i:s') . " | Name: {$name} | Email: {$email} | Phone: {$phone} | Interested: {$interested}\n";
    file_put_contents(__DIR__ . '/getstarted-log.txt', $logEntry, FILE_APPEND);

    // Send Firebase notification to topic (admin can subscribe to topic in mobile app)
    $firebase = new FirebaseNotification();
    $notificationResult = $firebase->sendTopicNotification(
        'oikos_get_started', // Topic name
        'New Get Started Inquiry',
        "$name is interested in: $interested",
        [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'interested' => $interested,
            'type' => 'inquiry'
        ]
    );
    
    error_log("Firebase notification result: " . json_encode($notificationResult));

    // Also send email as backup
    $emailSubject = "New Get Started Inquiry - Oikos Orchard & Farm";
    $emailBody = "New inquiry received:\n\n";
    $emailBody .= "Name: $name\n";
    $emailBody .= "Email: $email\n";
    $emailBody .= "Phone: $phone\n";
    $emailBody .= "Interested In: $interested\n";
    $emailBody .= "\nPlease follow up within 24 hours.";
    
    $emailHeaders = "From: " . $adminEmail . "\r\n";
    $emailHeaders .= "Content-Type: text/plain; charset=utf-8\r\n";
    
    mail($adminEmail, $emailSubject, $emailBody, $emailHeaders);

    // Respond with success
    http_response_code(200);
    $response = json_encode([
        'success' => true,
        'message' => 'Thank you! We have received your request. Our team will contact you within 24 hours.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    $response = json_encode([
        'success' => false,
        'message' => 'Server error. Please try again later.'
    ]);
}

// Clean output buffer and send response
ob_end_clean();
echo isset($response) ? $response : json_encode(['success' => false, 'message' => 'Unknown error']);
?>
