<?php

namespace Backender\Core;

use PDO;

class App
{
    public const VERSION = '0.1.1';
    
    private PDO $db;
    private Router $router;
    private string $storagePath;
    private bool $isDemoMode;
    
    public function __construct(string $storagePath = '/app/storage')
    {
        $this->storagePath = $storagePath;
        $this->isDemoMode = (getenv('DEMO_MODE') === 'true');
        $this->initializeStorage();
        $this->initializeDatabase();
        $this->router = new Router();
        
        // Create demo user if in demo mode and no users exist
        if ($this->isDemoMode && $this->isFirstRun()) {
            $this->createDemoUser();
        }
    }
    
    private function initializeStorage(): void
    {
        $dirs = [
            $this->storagePath,
            $this->storagePath . '/database',
            $this->storagePath . '/endpoints',
            $this->storagePath . '/logs'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    private function initializeDatabase(): void
    {
        $dbPath = $this->storagePath . '/database/backender.sqlite';
        $dbExists = file_exists($dbPath);
        
        $this->db = new PDO('sqlite:' . $dbPath);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if (!$dbExists) {
            $this->createSchema();
        }
    }
    
    private function createSchema(): void
    {
        $schema = <<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            email_verified_at DATETIME,
            verification_token TEXT,
            verification_expires DATETIME,
            reset_token TEXT,
            reset_expires DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS endpoints (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            method TEXT NOT NULL,
            path TEXT NOT NULL,
            enabled INTEGER DEFAULT 1,
            require_auth INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE UNIQUE INDEX IF NOT EXISTS idx_endpoint_route ON endpoints(method, path);
        
        CREATE TABLE IF NOT EXISTS api_keys (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key TEXT UNIQUE NOT NULL,
            label TEXT NOT NULL,
            last_used DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT NOT NULL,
            message TEXT NOT NULL,
            endpoint_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (endpoint_id) REFERENCES endpoints(id) ON DELETE SET NULL
        );
        SQL;
        
        $this->db->exec($schema);
    }
    
    public function getDatabase(): PDO
    {
        return $this->db;
    }
    
    public function getRouter(): Router
    {
        return $this->router;
    }
    
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }
    
    public function isDemoMode(): bool
    {
        return $this->isDemoMode;
    }
    
    public function isFirstRun(): bool
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM users');
        return $stmt->fetchColumn() === 0;
    }
    
    public function log(string $type, string $message, ?int $endpointId = null): void
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO logs (type, message, endpoint_id) VALUES (?, ?, ?)'
            );
            $stmt->execute([$type, $message, $endpointId]);
        } catch (\Exception $e) {
            error_log("Failed to write log: " . $e->getMessage());
        }
    }
    
    private function createDemoUser(): void
    {
        $stmt = $this->db->prepare('
                INSERT INTO users (email, password, email_verified_at, created_at)
            VALUES (?, ?, datetime("now"), datetime("now"))
        ');
        
        // Demo credentials: demo@backender.dev / DemoPass123!
        $hashedPassword = password_hash('DemoPass123!', PASSWORD_BCRYPT);
        $stmt->execute(['demo@backender.dev', $hashedPassword]);
    }
    
    public function clearAllData(): void
    {
        if (!$this->isDemoMode) {
            return; // Only allow in demo mode
        }
        
        // Delete all data
        $this->db->exec('DELETE FROM endpoints');
        $this->db->exec('DELETE FROM logs');
        $this->db->exec('DELETE FROM api_keys');
        
        // Delete endpoint files
        $endpointsDir = $this->storagePath . '/endpoints';
        if (is_dir($endpointsDir)) {
            $files = glob($endpointsDir . '/*.php');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        // Recreate demo user
        $this->db->exec('DELETE FROM users');
        $this->createDemoUser();
    }
}
