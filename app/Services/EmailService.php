<?php

namespace Backender\Services;

class EmailService
{
    private string $fromEmail;
    private string $fromName;
    private string $baseUrl;
    
    public function __construct()
    {
        $this->fromEmail = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@backender.local';
        $this->fromName = getenv('MAIL_FROM_NAME') ?: 'Backender';
        $this->baseUrl = getenv('APP_URL') ?: 'http://localhost:8080';
    }
    
    public function sendVerificationEmail(string $to, string $token): bool
    {
        $verifyUrl = $this->baseUrl . '/verify?token=' . urlencode($token);
        
        $subject = 'Verify Your Email - Backender';
        $message = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { display: inline-block; padding: 12px 24px; background: #4F46E5; color: white; 
                  text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; 
                  font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome to Backender!</h2>
        <p>Thanks for signing up. Please verify your email address by clicking the button below:</p>
        <a href="{$verifyUrl}" class="button">Verify Email Address</a>
        <p>Or copy and paste this link into your browser:</p>
        <p style="word-break: break-all; color: #4F46E5;">{$verifyUrl}</p>
        <p>This link will expire in 24 hours.</p>
        <div class="footer">
            <p>If you didn't create an account, you can safely ignore this email.</p>
        </div>
    </div>
</body>
</html>
HTML;
        
        return $this->send($to, $subject, $message);
    }
    
    public function sendPasswordResetEmail(string $to, string $token): bool
    {
        $resetUrl = $this->baseUrl . '/reset-password?token=' . urlencode($token);
        
        $subject = 'Reset Your Password - Backender';
        $message = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { display: inline-block; padding: 12px 24px; background: #DC2626; color: white; 
                  text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; 
                  font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Password Reset Request</h2>
        <p>We received a request to reset your password. Click the button below to create a new password:</p>
        <a href="{$resetUrl}" class="button">Reset Password</a>
        <p>Or copy and paste this link into your browser:</p>
        <p style="word-break: break-all; color: #DC2626;">{$resetUrl}</p>
        <p>This link will expire in 1 hour.</p>
        <div class="footer">
            <p>If you didn't request a password reset, you can safely ignore this email. Your password won't be changed.</p>
        </div>
    </div>
</body>
</html>
HTML;
        
        return $this->send($to, $subject, $message);
    }
    
    private function send(string $to, string $subject, string $htmlMessage): bool
    {
        // Check if we should use SMTP or mail()
        $mailDriver = getenv('MAIL_DRIVER') ?: 'log';
        
        if ($mailDriver === 'log') {
            // Development mode: log emails to file instead of sending
            return $this->logEmail($to, $subject, $htmlMessage);
        }
        
        // Production mode: use PHP's mail() function
        $headers = [
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        return mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
    }
    
    private function logEmail(string $to, string $subject, string $message): bool
    {
        $logPath = '/app/storage/logs/emails.log';
        $logEntry = sprintf(
            "[%s] To: %s | Subject: %s\n%s\n\n%s\n\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            str_repeat('=', 80),
            $message
        );
        
        return file_put_contents($logPath, $logEntry, FILE_APPEND) !== false;
    }
}
