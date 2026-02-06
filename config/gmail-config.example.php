<?php
// Gmail Configuration - EXAMPLE FILE
// Copy this to gmail-config.php and fill in your actual credentials
// gmail-config.php is in .gitignore and won't be committed

define('GMAIL_ADDRESS', 'YOUR_EMAIL@gmail.com');
define('GMAIL_APP_PASSWORD', 'YOUR_16_CHARACTER_APP_PASSWORD');
define('MAIL_FROM_NAME', 'Oikos Orchard & Farm');

// SMTP Settings (for reference - currently using basic mail())
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
?>
