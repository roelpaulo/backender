<?php

namespace Backender\Http;

class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $input;
    private array $headers;
    private ?array $jsonData = null;
    
    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->query = $_GET ?? [];
        $this->input = $_POST ?? [];
        $this->headers = $this->parseHeaders();
        
        // Parse JSON body if content-type is application/json
        if ($this->isJson()) {
            $rawInput = file_get_contents('php://input');
            $this->jsonData = json_decode($rawInput, true);
            if (is_array($this->jsonData)) {
                $this->input = $this->jsonData;
            }
        }
    }
    
    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }
    
    private function isJson(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return strpos($contentType, 'application/json') !== false;
    }
    
    public function method(): string
    {
        return $this->method;
    }
    
    public function path(): string
    {
        return $this->path;
    }
    
    public function query(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }
    
    public function input(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->input;
        }
        return $this->input[$key] ?? $default;
    }
    
    public function json(): ?array
    {
        return $this->jsonData;
    }
    
    public function headers(): array
    {
        return $this->headers;
    }
    
    public function header(string $key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }
}
