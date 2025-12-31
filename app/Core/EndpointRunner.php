<?php

namespace Backender\Core;

use Backender\Http\Request;
use Backender\Http\Response;
use Backender\Services\ApiKeyService;
use PDO;

class EndpointRunner
{
    private string $logicFilePath;
    private PDO $db;
    private bool $requireAuth;
    private ApiKeyService $apiKeyService;
    
    public function __construct(string $logicFilePath, PDO $db, bool $requireAuth = false)
    {
        $this->logicFilePath = $logicFilePath;
        $this->db = $db;
        $this->requireAuth = $requireAuth;
        $this->apiKeyService = new ApiKeyService($db);
    }
    
    public function execute(Request $request): Response
    {
        // Check authentication if required
        if ($this->requireAuth && !$this->checkAuthentication($request)) {
            return Response::json([
                'error' => 'Unauthorized',
                'message' => 'Valid API key required. Include "X-API-Key" header.'
            ], 401)->withCors();
        }
        
        if (!file_exists($this->logicFilePath)) {
            return Response::json(['error' => 'Endpoint logic file not found'], 500);
        }
        
        try {
            // Load the endpoint logic file
            $handler = require $this->logicFilePath;
            
            if (!is_callable($handler)) {
                return Response::json(['error' => 'Endpoint must return a callable'], 500);
            }
            
            // Execute the handler with only the request
            $result = $handler($request);
            
            // Normalize the response
            return $this->normalizeResponse($result);
            
        } catch (\Throwable $e) {
            error_log("Endpoint execution error: " . $e->getMessage());
            return Response::json([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    private function checkAuthentication(Request $request): bool
    {
        // Check for API key in X-Api-Key header (HTTP_X_API_KEY)
        $apiKey = $request->header('X-Api-Key');
        
        if (empty($apiKey)) {
            // Also check Authorization header with Bearer format
            $authHeader = $request->header('Authorization');
            if ($authHeader && preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
                $apiKey = $matches[1];
            }
        }
        
        // Return false if no API key provided
        if (empty($apiKey)) {
            return false;
        }
        
        return $this->apiKeyService->validateKey($apiKey);
    }
    
    private function normalizeResponse($result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }
        
        if (is_array($result)) {
            return Response::json($result)->withCors();
        }
        
        if (is_string($result)) {
            return Response::text($result)->withCors();
        }
        
        // For other types, convert to JSON
        return Response::json(['data' => $result])->withCors();
    }
}
