<?php
/**
 * PHPMailer Exception class
 * Simplified version for basic email functionality
 */

class PHPMailerException extends Exception
{
    /**
     * Prettify error message output
     */
    public function errorMessage()
    {
        return '<strong>' . htmlspecialchars($this->getMessage()) . "</strong>";
    }
}
?>
