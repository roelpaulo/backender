<?php

namespace Backender\Http;

class Response
{
    private $body;
    private int $status;
    private array $headers;
    
    public function __construct($body, int $status = 200, array $headers = [])
    {
        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers;
    }
    
    public static function json(array $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $status,
            ['Content-Type' => 'application/json']
        );
    }
    
    public static function text(string $text, int $status = 200): self
    {
        return new self(
            $text,
            $status,
            ['Content-Type' => 'text/plain']
        );
    }
    
    public function withCors(string $origin = '*', array $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS']): self
    {
        $this->headers['Access-Control-Allow-Origin'] = $origin;
        $this->headers['Access-Control-Allow-Methods'] = implode(', ', $methods);
        $this->headers['Access-Control-Allow-Headers'] = 'Content-Type, Authorization, X-Requested-With, X-API-Key';
        $this->headers['Access-Control-Max-Age'] = '86400';
        return $this;
    }
    
    public static function html(string $html, int $status = 200): self
    {
        return new self(
            $html,
            $status,
            ['Content-Type' => 'text/html']
        );
    }
    
    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }
    
    public function send(): void
    {
        http_response_code($this->status);
        
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        
        echo $this->body;
    }
    
    public function getStatus(): int
    {
        return $this->status;
    }
    
    public function getBody()
    {
        return $this->body;
    }
    
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
