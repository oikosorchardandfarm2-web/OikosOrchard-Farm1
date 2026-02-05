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

    public function send()
    {
        try {
            $this->connection = fsockopen(
                'tls://' . $this->Host,
                $this->Port,
                $errno,
                $errstr,
                10
            );

            if (!$this->connection) {
                throw new Exception("Could not connect to SMTP host");
            }

            // Read server response
            $this->getResponse();

            // Send EHLO
            $this->sendCommand("EHLO localhost");
            $this->getResponse();

            // Start TLS (already connected via tls://)
            $this->sendCommand("AUTH LOGIN");
            $this->getResponse();

            // Send username (base64 encoded)
            $this->sendCommand(base64_encode($this->Username));
            $this->getResponse();

            // Send password (base64 encoded)
            $this->sendCommand(base64_encode($this->Password));
            $response = $this->getResponse();

            if (strpos($response, '235') === false) {
                throw new Exception("SMTP authentication failed");
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
