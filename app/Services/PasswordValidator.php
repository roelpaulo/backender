<?php

namespace Backender\Services;

class PasswordValidator
{
    private int $minLength = 8;
    private bool $requireUppercase = true;
    private bool $requireLowercase = true;
    private bool $requireNumber = true;
    private bool $requireSpecialChar = true;
    
    public function validate(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < $this->minLength) {
            $errors[] = "Password must be at least {$this->minLength} characters long";
        }
        
        if ($this->requireUppercase && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if ($this->requireLowercase && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if ($this->requireNumber && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if ($this->requireSpecialChar && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character (!@#$%^&* etc.)";
        }
        
        return $errors;
    }
    
    public function isValid(string $password): bool
    {
        return empty($this->validate($password));
    }
    
    public function getRequirements(): string
    {
        $requirements = [];
        $requirements[] = "At least {$this->minLength} characters";
        if ($this->requireUppercase) $requirements[] = "One uppercase letter";
        if ($this->requireLowercase) $requirements[] = "One lowercase letter";
        if ($this->requireNumber) $requirements[] = "One number";
        if ($this->requireSpecialChar) $requirements[] = "One special character";
        
        return implode(', ', $requirements);
    }
}
