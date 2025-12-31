<?php

namespace Backender\Core;

use Backender\Http\Request;
use PDO;

class Router
{
    private array $routes = [];
    private PDO $db;
    private string $storagePath;
    
    public function setDatabase(PDO $db): void
    {
        $this->db = $db;
    }
    
    public function setStoragePath(string $storagePath): void
    {
        $this->storagePath = $storagePath;
    }
    
    public function match(Request $request): ?array
    {
        // Load routes from database
        $stmt = $this->db->prepare(
            'SELECT id, method, path, require_auth FROM endpoints WHERE enabled = 1'
        );
        $stmt->execute();
        $endpoints = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($endpoints as $endpoint) {
            if ($this->matchRoute($request, $endpoint['method'], $endpoint['path'])) {
                return $endpoint;
            }
        }
        
        return null;
    }
    
    private function matchRoute(Request $request, string $method, string $path): bool
    {
        // Exact method and path match
        return strtoupper($request->method()) === strtoupper($method) 
            && $request->path() === $path;
    }
    
    public function getEndpointLogicPath(int $endpointId): string
    {
        return $this->storagePath . '/endpoints/' . $endpointId . '.php';
    }
}
