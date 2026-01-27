<?php
/**
 * Google Sheets Integration for Oikos Orchard & Farm Bookings
 * 
 * This file handles sending booking data to Google Sheets via Google Apps Script
 * 
 * SETUP INSTRUCTIONS:
 * 1. Create a Google Sheet for bookings
 * 2. Go to Google Apps Script: https://script.google.com
 * 3. Create a new project
 * 4. Paste the code from google-apps-script.gs
 * 5. Deploy as Web App (Execute as: your account, Who has access: Anyone)
 * 6. Copy the deployment URL
 * 7. Update GOOGLE_SHEETS_WEBHOOK_URL below
 */

// ============ CONFIGURATION ============
// Replace this with your Google Apps Script deployment URL
define('GOOGLE_SHEETS_WEBHOOK_URL', 'https://script.google.com/macros/s/AKfycbyfgMWh3i6EvBrf6yyNkrHsX7LFUYXTvzZ3C95oEI7DVcDOmWLXOUdj1j4PMbag_-fI7w/exec');

// ============ FUNCTION TO SEND DATA TO GOOGLE SHEETS ============
function sendToGoogleSheets($bookingData) {
    // Check if webhook URL is configured
    if (GOOGLE_SHEETS_WEBHOOK_URL === 'https://script.google.com/macros/d/YOUR_SCRIPT_ID/userweb?v=YOUR_VERSION_ID') {
        error_log('Google Sheets webhook not configured. Update GOOGLE_SHEETS_WEBHOOK_URL in google-sheets-integration.php');
        return false;
    }

    try {
        // Prepare data for Google Sheets
        $payload = json_encode([
            'fullName' => $bookingData['fullName'] ?? '',
            'email' => $bookingData['email'] ?? '',
            'phone' => $bookingData['phone'] ?? '',
            'checkinDate' => $bookingData['checkinDate'] ?? '',
            'guests' => $bookingData['guests'] ?? '',
            'packageName' => $bookingData['packageName'] ?? '',
            'packagePrice' => $bookingData['packagePrice'] ?? '',
            'specialRequests' => $bookingData['specialRequests'] ?? '',
            'timestamp' => $bookingData['timestamp'] ?? date('Y-m-d H:i:s'),
            'bookingId' => $bookingData['id'] ?? ''
        ]);

        // Set up cURL request
        $ch = curl_init(GOOGLE_SHEETS_WEBHOOK_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        error_log('=== Google Sheets Webhook Call ===');
        error_log('URL: ' . GOOGLE_SHEETS_WEBHOOK_URL);
        error_log('Booking ID: ' . $bookingData['id']);
        error_log('HTTP Code: ' . $httpCode);
        error_log('cURL Error: ' . ($curlError ? $curlError : 'None'));
        error_log('Response: ' . $response);
        error_log('Payload: ' . $payload);
        error_log('================================');

        if ($httpCode >= 200 && $httpCode < 300) {
            error_log('✓ Successfully sent booking to Google Sheets: ' . $bookingData['id']);
            return true;
        } else {
            error_log('✗ Failed to send to Google Sheets. HTTP Code: ' . $httpCode . ' Response: ' . $response);
            return false;
        }

    } catch (Exception $e) {
        error_log('Error sending to Google Sheets: ' . $e->getMessage());
        return false;
    }
}

?>
