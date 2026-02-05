<?php
/**
 * Helper function to send emails via Gmail SMTP
 * Direct SMTP connection - more reliable than PHP mail()
 */

function sendEmailViaSMTP($to, $subject, $body, $fromEmail, $fromName, $username, $password) {
    $host = "smtp.gmail.com";
    $port = 587;
    
    try {
        // Create socket connection
        $socket = fsockopen($host, $port, $errno, $errstr, 10);
        
        if (!$socket) {
            error_log("SMTP: Could not connect - $errstr");
            return false;
        }
        
        // Read greeting
        stream_get_line($socket, 512, "\r\n");
        
        // Send EHLO
        fwrite($socket, "EHLO localhost\r\n");
        while ($line = stream_get_line($socket, 512, "\r\n")) {
            if (substr($line, 3, 1) === ' ') break;
        }
        
        // Start TLS
        fwrite($socket, "STARTTLS\r\n");
        stream_get_line($socket, 512, "\r\n");
        
        // Enable crypto
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
            error_log("SMTP: TLS failed");
            fclose($socket);
            return false;
        }
        
        // Send EHLO again after TLS
        fwrite($socket, "EHLO localhost\r\n");
        while ($line = stream_get_line($socket, 512, "\r\n")) {
            if (substr($line, 3, 1) === ' ') break;
        }
        
        // Auth LOGIN
        fwrite($socket, "AUTH LOGIN\r\n");
        stream_get_line($socket, 512, "\r\n");
        
        // Send username (base64)
        fwrite($socket, base64_encode($username) . "\r\n");
        $resp = stream_get_line($socket, 512, "\r\n");
        if (strpos($resp, '235') === false) {
            error_log("SMTP: Auth failed at username");
            fclose($socket);
            return false;
        }
        
        // Send password (base64)
        fwrite($socket, base64_encode($password) . "\r\n");
        $resp = stream_get_line($socket, 512, "\r\n");
        if (strpos($resp, '235') === false) {
            error_log("SMTP: Auth failed at password - " . $resp);
            fclose($socket);
            return false;
        }
        
        // MAIL FROM
        fwrite($socket, "MAIL FROM:<$fromEmail>\r\n");
        stream_get_line($socket, 512, "\r\n");
        
        // RCPT TO
        fwrite($socket, "RCPT TO:<$to>\r\n");
        stream_get_line($socket, 512, "\r\n");
        
        // DATA
        fwrite($socket, "DATA\r\n");
        stream_get_line($socket, 512, "\r\n");
        
        // Write email
        $header = "From: \"$fromName\" <$fromEmail>\r\n";
        $header .= "To: $to\r\n";
        $header .= "Subject: $subject\r\n";
        $header .= "Content-Type: text/plain; charset=utf-8\r\n";
        $header .= "X-Mailer: Oikos\r\n";
        $header .= "\r\n";
        
        fwrite($socket, $header . $body . "\r\n.\r\n");
        stream_get_line($socket, 512, "\r\n");
        
        // QUIT
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        
        return true;
    } catch (Exception $e) {
        error_log("SMTP Exception: " . $e->getMessage());
        return false;
    }
}

?>
