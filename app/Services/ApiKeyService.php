<?php

namespace Backender\Services;

use PDO;

class ApiKeyService
{
    private PDO $db;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    /**
     * Generate a new API key
     */
    public function generateKey(string $label): array
    {
        // Generate a secure random key with prefix
        $key = 'bk_' . bin2hex(random_bytes(32));
        
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO api_keys (key, label) VALUES (?, ?)'
            );
            $stmt->execute([$key, $label]);
            
            return [
                'success' => true,
                'key' => $key,
                'id' => $this->db->lastInsertId()
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Validate an API key
     */
    public function validateKey(string $key): bool
    {
        if (empty($key)) {
            return false;
        }
        
        $stmt = $this->db->prepare('SELECT id FROM api_keys WHERE key = ?');
        $stmt->execute([$key]);
        $apiKey = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($apiKey) {
            // Update last_used timestamp
            $updateStmt = $this->db->prepare(
                'UPDATE api_keys SET last_used = datetime("now") WHERE id = ?'
            );
            $updateStmt->execute([$apiKey['id']]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get all API keys
     */
    public function getAllKeys(): array
    {
        $stmt = $this->db->query(
            'SELECT id, key, label, last_used, created_at FROM api_keys ORDER BY created_at DESC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Delete an API key
     */
    public function deleteKey(int $id): bool
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM api_keys WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Mask API key for display (show only first and last 8 chars)
     */
    public static function maskKey(string $key): string
    {
        if (strlen($key) <= 16) {
            return $key;
        }
        
        $start = substr($key, 0, 8);
        $end = substr($key, -8);
        
        return $start . '...' . $end;
    }
}
