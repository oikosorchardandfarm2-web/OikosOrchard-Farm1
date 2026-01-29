<?php
// Start output buffering to prevent accidental output before JSON
ob_start();

// Set strict error reporting but don't display to user
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// Include email helper with PHPMailer
require_once __DIR__ . '/send-email-helper.php';

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

    // Send emails via Gmail SMTP using PHPMailer
    sendGetStartedEmailsViaGmail($email, $name, $phone, $interested);

    // Log the request for records
    $logEntry = date('Y-m-d H:i:s') . " | Name: {$name} | Email: {$email} | Phone: {$phone} | Interested: {$interested}\n";
    file_put_contents(__DIR__ . '/getstarted-log.txt', $logEntry, FILE_APPEND);

    // Respond with success
    http_response_code(200);
    $response = json_encode([
        'success' => true,
        'message' => 'Thank you! We have received your request and will contact you shortly. Check your email for confirmation.'
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
?>
