<?php

namespace Backender\Services;

use PDO;

class Auth
{
    private PDO $db;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function createUser(string $email, string $password): array
    {
        try {
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'error' => 'Invalid email address'];
            }
            
            // Check if email already exists
            $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Email already registered'];
            }
            
            // Hash password and generate verification token
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $verificationToken = bin2hex(random_bytes(32));
            $verificationExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $stmt = $this->db->prepare(
                'INSERT INTO users (email, password, verification_token, verification_expires) 
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$email, $hashedPassword, $verificationToken, $verificationExpires]);
            
            return [
                'success' => true, 
                'user_id' => $this->db->lastInsertId(),
                'verification_token' => $verificationToken
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function login(string $email, string $password): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, email, password, email_verified_at FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid email or password'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'Invalid email or password'];
        }
        
        if (!$user['email_verified_at']) {
            return ['success' => false, 'error' => 'Please verify your email address before logging in'];
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        
        return ['success' => true];
    }
    
    public function verifyEmail(string $token): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM users 
             WHERE verification_token = ? 
             AND verification_expires > datetime("now")
             AND email_verified_at IS NULL'
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        $stmt = $this->db->prepare(
            'UPDATE users 
             SET email_verified_at = datetime("now"), 
                 verification_token = NULL, 
                 verification_expires = NULL 
             WHERE id = ?'
        );
        return $stmt->execute([$user['id']]);
    }
    
    public function requestPasswordReset(string $email): array
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // Don't reveal if email exists or not
            return ['success' => true, 'message' => 'If that email exists, a reset link has been sent'];
        }
        
        $resetToken = bin2hex(random_bytes(32));
        $resetExpires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $this->db->prepare(
            'UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?'
        );
        $stmt->execute([$resetToken, $resetExpires, $user['id']]);
        
        return [
            'success' => true, 
            'reset_token' => $resetToken,
            'message' => 'If that email exists, a reset link has been sent'
        ];
    }
    
    public function resetPassword(string $token, string $newPassword): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM users 
             WHERE reset_token = ? 
             AND reset_expires > datetime("now")'
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            'UPDATE users 
             SET password = ?, 
                 reset_token = NULL, 
                 reset_expires = NULL 
             WHERE id = ?'
        );
        return $stmt->execute([$hashedPassword, $user['id']]);
    }
    
    public function logout(): void
    {
        session_destroy();
        session_start();
    }
    
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }
    
    public function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }
    
    public function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function getEmail(): ?string
    {
        return $_SESSION['email'] ?? null;
    }
}

