<?php
/**
 * Get Started Form Handler
 * Sends WhatsApp message to admin when user submits Get Started form
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

    // Admin WhatsApp number (international format) - for reference only
    $adminWhatsApp = '639177770851'; // +63 917 777 0851

    // Log the inquiry
    $logEntry = date('Y-m-d H:i:s') . " | Name: {$name} | Email: {$email} | Phone: {$phone} | Interested: {$interested}\n";
    @file_put_contents(__DIR__ . '/getstarted-log.txt', $logEntry, FILE_APPEND);
    
    // Log entry with detailed info
    $detailedLog = "=== GET STARTED SUBMISSION ===\n";
    $detailedLog .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    $detailedLog .= "Name: {$name}\n";
    $detailedLog .= "Email: {$email}\n";
    $detailedLog .= "Phone: {$phone}\n";
    $detailedLog .= "Interested In: {$interested}\n";
    $detailedLog .= "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
    $detailedLog .= "==============================\n\n";
    @file_put_contents(__DIR__ . '/getstarted-detailed.txt', $detailedLog, FILE_APPEND);

    // Respond with success
    http_response_code(200);
    $response = json_encode([
        'success' => true,
        'message' => 'Thank you for your interest! Our team will contact you within 24 hours.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    $response = json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

// Clean output buffer and send response
ob_end_clean();
echo isset($response) ? $response : json_encode(['success' => false, 'message' => 'Unknown error']);
?>

