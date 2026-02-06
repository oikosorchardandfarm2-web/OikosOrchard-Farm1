<?php
// Twilio SMS Configuration - EXAMPLE FILE
// Copy this to twilio-config.php and fill in your actual credentials
// twilio-config.php is in .gitignore and won't be committed

define('TWILIO_ACCOUNT_SID', 'YOUR_ACCOUNT_SID_HERE');
define('TWILIO_AUTH_TOKEN', 'YOUR_AUTH_TOKEN_HERE');

// Messaging Service (for sending SMS without phone number restrictions)
define('TWILIO_MESSAGING_SERVICE_SID', 'YOUR_MESSAGING_SERVICE_SID_HERE');

// Recipient phone number for notifications
define('NOTIFY_PHONE_NUMBER', '+639948962820'); // Your Philippines number
?>
