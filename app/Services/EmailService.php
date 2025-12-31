<?php

namespace Backender\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private string $fromEmail;
    private string $fromName;
    private string $baseUrl;
    private string $mailDriver;
    
    public function __construct()
    {
        $this->fromEmail = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@backender.local';
        $this->fromName = getenv('MAIL_FROM_NAME') ?: 'Backender';
        $this->baseUrl = getenv('APP_URL') ?: 'http://localhost:8080';
        $this->mailDriver = getenv('MAIL_DRIVER') ?: 'log';
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
        // Development mode: log emails to file
        if ($this->mailDriver === 'log') {
            return $this->logEmail($to, $subject, $htmlMessage);
        }
        
        // Production mode: use PHPMailer with SMTP
        try {
            $mail = new PHPMailer(true);
            
            // Enable debug output (will be logged to PHP error log)
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug [$level]: $str");
            };
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('SMTP_USERNAME') ?: $this->fromEmail;
            $mail->Password = getenv('SMTP_PASSWORD') ?: '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
            
            // Log configuration for debugging
            error_log("Email Configuration - Driver: {$this->mailDriver}, Host: {$mail->Host}, Port: {$mail->Port}, User: {$mail->Username}, From: {$this->fromEmail}");
            
            // Recipients
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlMessage;
            $mail->AltBody = strip_tags($htmlMessage);
            
            $mail->send();
            error_log("Email sent successfully to: $to");
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed to $to: {$mail->ErrorInfo}");
            error_log("Exception: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            // Also log to emails.log for easier debugging
            $this->logEmail($to, $subject, "FAILED TO SEND: {$mail->ErrorInfo}\n\n" . $htmlMessage);
            return false;
        }
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
