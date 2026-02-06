<?php
/**
 * Simplified PHPMailer for Gmail SMTP
 */

class PHPMailer
{
    public $Host = '';
    public $Port = 587;
    public $SMTPSecure = 'tls';
    public $SMTPAuth = true;
    public $Username = '';
    public $Password = '';
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $isHTML = true;
    public $CharSet = 'UTF-8';
    public $ErrorInfo = '';
    
    private $to = [];
    private $cc = [];
    private $bcc = [];
    private $ReplyTo = [];
    private $connection = false;

    public function addAddress($address, $name = '')
    {
        $this->to[] = ['address' => $address, 'name' => $name];
    }

    public function addCC($address, $name = '')
    {
        $this->cc[] = ['address' => $address, 'name' => $name];
    }

    public function addBCC($address, $name = '')
    {
        $this->bcc[] = ['address' => $address, 'name' => $name];
    }

    public function addReplyTo($address, $name = '')
    {
        $this->ReplyTo[] = ['address' => $address, 'name' => $name];
    }

    public function isSMTP()
    {
        return true;
    }

    public function setFrom($address, $name = '')
    {
        $this->From = $address;
        $this->FromName = $name;
    }

    public function clearAddresses()
    {
        $this->to = [];
    }

    public function send()
    {
        try {
            // Connect to SMTP server (plain connection first)
            $this->connection = fsockopen(
                $this->Host,
                $this->Port,
                $errno,
                $errstr,
                10
            );

            if (!$this->connection) {
                throw new Exception("Could not connect to SMTP host: $errstr");
            }

            // Read server response
            $response = $this->getResponse();
            if (strpos($response, '220') === false) {
                throw new Exception("Invalid server response: $response");
            }

            // Send EHLO
            $this->sendCommand("EHLO localhost");
            $response = $this->getResponse();

            // Start TLS if configured
            if ($this->SMTPSecure === 'tls') {
                $this->sendCommand("STARTTLS");
                $response = $this->getResponse();
                
                if (strpos($response, '220') === false) {
                    throw new Exception("STARTTLS failed: $response");
                }

                // Enable TLS encryption on the connection
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (!stream_socket_enable_crypto($this->connection, true, $cryptoMethod)) {
                    throw new Exception("Failed to enable TLS encryption");
                }

                // Send EHLO again after STARTTLS
                $this->sendCommand("EHLO localhost");
                $response = $this->getResponse();
            }

            // Authenticate
            if ($this->SMTPAuth) {
                $this->sendCommand("AUTH LOGIN");
                $response = $this->getResponse();
                
                if (strpos($response, '334') === false) {
                    throw new Exception("AUTH LOGIN not supported: $response");
                }

                // Send username (base64 encoded)
                $this->sendCommand(base64_encode($this->Username));
                $response = $this->getResponse();
                
                if (strpos($response, '334') === false) {
                    throw new Exception("Username rejected: $response");
                }

                // Send password (base64 encoded)
                $this->sendCommand(base64_encode($this->Password));
                $response = $this->getResponse();

                if (strpos($response, '235') === false) {
                    throw new Exception("Authentication failed: $response");
                }
            }

            // Send MAIL FROM
            $this->sendCommand("MAIL FROM:<" . $this->From . ">");
            $this->getResponse();

            // Send RCPT TO for each recipient
            foreach ($this->to as $recipient) {
                $this->sendCommand("RCPT TO:<" . $recipient['address'] . ">");
                $this->getResponse();
            }

            foreach ($this->cc as $recipient) {
                $this->sendCommand("RCPT TO:<" . $recipient['address'] . ">");
                $this->getResponse();
            }

            foreach ($this->bcc as $recipient) {
                $this->sendCommand("RCPT TO:<" . $recipient['address'] . ">");
                $this->getResponse();
            }

            // Send DATA
            $this->sendCommand("DATA");
            $this->getResponse();

            // Build message
            $message = $this->buildMessage();
            $this->sendCommand($message . "\r\n.");
            $this->getResponse();

            // Send QUIT
            $this->sendCommand("QUIT");
            $this->getResponse();

            fclose($this->connection);
            return true;

        } catch (Exception $e) {
            if ($this->connection) {
                fclose($this->connection);
            }
            $this->ErrorInfo = $e->getMessage();
            throw $e;
        }
    }

    private function sendCommand($command)
    {
        fwrite($this->connection, $command . "\r\n");
    }

    private function getResponse()
    {
        $response = '';
        while ($line = fgets($this->connection, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }

    private function buildMessage()
    {
        $message = '';
        
        // Headers
        $message .= "From: " . $this->FromName . " <" . $this->From . ">\r\n";
        
        if (!empty($this->to)) {
            $toAddresses = [];
            foreach ($this->to as $recipient) {
                $toAddresses[] = (!empty($recipient['name']) ? $recipient['name'] . ' <' . $recipient['address'] . '>' : $recipient['address']);
            }
            $message .= "To: " . implode(", ", $toAddresses) . "\r\n";
        }

        if (!empty($this->cc)) {
            $ccAddresses = [];
            foreach ($this->cc as $recipient) {
                $ccAddresses[] = (!empty($recipient['name']) ? $recipient['name'] . ' <' . $recipient['address'] . '>' : $recipient['address']);
            }
            $message .= "Cc: " . implode(", ", $ccAddresses) . "\r\n";
        }

        if (!empty($this->ReplyTo)) {
            $replyAddresses = [];
            foreach ($this->ReplyTo as $recipient) {
                $replyAddresses[] = $recipient['address'];
            }
            $message .= "Reply-To: " . implode(", ", $replyAddresses) . "\r\n";
        }

        $message .= "Subject: " . $this->Subject . "\r\n";
        $message .= "MIME-Version: 1.0\r\n";

        if ($this->isHTML) {
            $message .= "Content-Type: text/html; charset=" . $this->CharSet . "\r\n";
        } else {
            $message .= "Content-Type: text/plain; charset=" . $this->CharSet . "\r\n";
        }

        $message .= "Content-Transfer-Encoding: 8bit\r\n";
        $message .= "\r\n";
        $message .= $this->Body;

        return $message;
    }
}
?>
