<?php

namespace Backender\Controllers;

use Backender\Core\App;
use Backender\Http\Response;
use Backender\Services\Auth;
use Backender\Services\EmailService;
use Backender\Services\PasswordValidator;

class AdminController
{
    private App $app;
    private Auth $auth;
    private EmailService $emailService;
    private PasswordValidator $passwordValidator;
    
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->auth = new Auth($app->getDatabase());
        $this->emailService = new EmailService();
        $this->passwordValidator = new PasswordValidator();
    }
    
    private function view(string $view, array $data = []): Response
    {
        extract($data);
        ob_start();
        include __DIR__ . '/../Views/' . $view . '.php';
        $content = ob_get_clean();
        
        ob_start();
        include __DIR__ . '/../Views/layout.php';
        $html = ob_get_clean();
        
        return Response::html($html);
    }
    
    public function setup(): Response
    {
        if (!$this->app->isFirstRun()) {
            return Response::redirect('/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['password_confirm'] ?? '';
            
            if (empty($email) || empty($password)) {
                return $this->view('setup', [
                    'title' => 'Setup',
                    'error' => 'Email and password are required',
                    'showNav' => false,
                    'passwordRequirements' => $this->passwordValidator->getRequirements()
                ]);
            }
            
            // Validate password complexity
            $passwordErrors = $this->passwordValidator->validate($password);
            if (!empty($passwordErrors)) {
                return $this->view('setup', [
                    'title' => 'Setup',
                    'error' => implode('. ', $passwordErrors),
                    'showNav' => false,
                    'passwordRequirements' => $this->passwordValidator->getRequirements()
                ]);
            }
            
            if ($password !== $confirm) {
                return $this->view('setup', [
                    'title' => 'Setup',
                    'error' => 'Passwords do not match',
                    'showNav' => false,
                    'passwordRequirements' => $this->passwordValidator->getRequirements()
                ]);
            }
            
            $result = $this->auth->createUser($email, $password);
            if ($result['success']) {
                // Send verification email
                $this->emailService->sendVerificationEmail($email, $result['verification_token']);
                
                return $this->view('setup', [
                    'title' => 'Setup',
                    'success' => 'Account created! Please check your email to verify your address.',
                    'showNav' => false,
                    'passwordRequirements' => $this->passwordValidator->getRequirements()
                ]);
            }
            
            return $this->view('setup', [
                'title' => 'Setup',
                'error' => $result['error'] ?? 'Failed to create user',
                'showNav' => false,
                'passwordRequirements' => $this->passwordValidator->getRequirements()
            ]);
        }
        
        return $this->view('setup', [
            'title' => 'Setup',
            'showNav' => false,
            'passwordRequirements' => $this->passwordValidator->getRequirements()
        ]);
    }
    
    public function login(): Response
    {
        if ($this->auth->isLoggedIn()) {
            return Response::redirect('/');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $result = $this->auth->login($email, $password);
            if ($result['success']) {
                return Response::redirect('/');
            }
            
            return $this->view('login', [
                'title' => 'Login',
                'error' => $result['error'] ?? 'Invalid credentials',
                'showNav' => false,
                'isDemoMode' => $this->app->isDemoMode()
            ]);
        }
        
        return $this->view('login', [
            'title' => 'Login',
            'showNav' => false,
            'isDemoMode' => $this->app->isDemoMode()
        ]);
    }
    
    public function verify(): Response
    {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            return $this->view('verify', [
                'title' => 'Email Verification',
                'error' => 'Invalid verification link',
                'showNav' => false
            ]);
        }
        
        if ($this->auth->verifyEmail($token)) {
            return $this->view('verify', [
                'title' => 'Email Verification',
                'success' => 'Email verified successfully! You can now log in.',
                'showNav' => false
            ]);
        }
        
        return $this->view('verify', [
            'title' => 'Email Verification',
            'error' => 'Invalid or expired verification link',
            'showNav' => false
        ]);
    }
    
    public function forgotPassword(): Response
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            
            if (empty($email)) {
                return $this->view('forgot-password', [
                    'title' => 'Forgot Password',
                    'error' => 'Email is required',
                    'showNav' => false
                ]);
            }
            
            $result = $this->auth->requestPasswordReset($email);
            if (isset($result['reset_token'])) {
                $this->emailService->sendPasswordResetEmail($email, $result['reset_token']);
            }
            
            return $this->view('forgot-password', [
                'title' => 'Forgot Password',
                'success' => $result['message'],
                'showNav' => false
            ]);
        }
        
        return $this->view('forgot-password', [
            'title' => 'Forgot Password',
            'showNav' => false
        ]);
    }
    
    public function resetPassword(): Response
    {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            return Response::redirect('/forgot-password');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['password_confirm'] ?? '';
            
            if (empty($password)) {
                return $this->view('reset-password', [
                    'title' => 'Reset Password',
                    'error' => 'Password is required',
                    'showNav' => false,
                    'token' => $token,
                    'passwordRequirements' => $this->passwordValidator->getRequirements()
                ]);
            }
            
            $passwordErrors = $this->passwordValidator->validate($password);
            if (!empty($passwordErrors)) {
                return $this->view('reset-password', [
                    'title' => 'Reset Password',
                    'error' => implode('. ', $passwordErrors),
                    'showNav' => false,
                    'token' => $token,
                    'passwordRequirements' => $this->passwordValidator->getRequirements()
                ]);
            }
            
            if ($password !== $confirm) {
                return $this->view('reset-password', [
                    'title' => 'Reset Password',
                    'error' => 'Passwords do not match',
                    'showNav' => false,
                    'token' => $token,
                    'passwordRequirements' => $this->passwordValidator->getRequirements()
                ]);
            }
            
            if ($this->auth->resetPassword($token, $password)) {
                return $this->view('reset-password', [
                    'title' => 'Reset Password',
                    'success' => 'Password reset successfully! You can now log in.',
                    'showNav' => false,
                    'token' => $token,
                    'passwordRequirements' => $this->passwordValidator->getRequirements()
                ]);
            }
            
            return $this->view('reset-password', [
                'title' => 'Reset Password',
                'error' => 'Invalid or expired reset link',
                'showNav' => false,
                'token' => $token,
                'passwordRequirements' => $this->passwordValidator->getRequirements()
            ]);
        }
        
        return $this->view('reset-password', [
            'title' => 'Reset Password',
            'showNav' => false,
            'token' => $token,
            'passwordRequirements' => $this->passwordValidator->getRequirements()
        ]);
    }
    
    public function logout(): Response
    {
        // Clear all data in demo mode
        if ($this->app->isDemoMode()) {
            $this->app->clearAllData();
        }
        
        $this->auth->logout();
        return Response::redirect('/login');
    }
    
    public function dashboard(): Response
    {
        $this->auth->requireAuth();
        
        $stmt = $this->app->getDatabase()->query(
            'SELECT * FROM endpoints ORDER BY created_at DESC'
        );
        $endpoints = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return $this->view('dashboard', [
            'title' => 'Dashboard',
            'currentPage' => 'dashboard',
            'showNav' => true,
            'endpoints' => $endpoints
        ]);
    }
    
    public function logs(): Response
    {
        $this->auth->requireAuth();
        
        $stmt = $this->app->getDatabase()->query(
            'SELECT * FROM logs ORDER BY created_at DESC LIMIT 100'
        );
        $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return $this->view('logs', [
            'title' => 'Logs',
            'currentPage' => 'logs',
            'showNav' => true,
            'logs' => $logs
        ]);
    }
}
