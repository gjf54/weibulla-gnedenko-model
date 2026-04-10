<?php
namespace App\Core;

class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $post;
    private array $files;
    private array $cookies;
    private array $headers;
    private array $server;
    private ?array $json = null;
    
    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = $this->parsePath();
        $this->query = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->headers = $this->parseHeaders();
        $this->server = $_SERVER;
        
        // Парсинг JSON тела запроса
        if ($this->isJson()) {
            $this->json = json_decode(file_get_contents('php://input'), true);
        }
    }
    
    private function parsePath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH);
        
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }
        
        return $uri;
    }
    
    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace('_', '-', substr($key, 5));
                $header = ucwords(strtolower($header), '-');
                $headers[$header] = $value;
            }
        }
        return $headers;
    }
    
    public function isJson(): bool
    {
        return str_contains($this->getHeader('Content-Type', ''), 'application/json');
    }
    
    public function getMethod(): string
    {
        return $this->method;
    }
    
    public function getPath(): string
    {
        return $this->path;
    }
    
    public function get(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }
    
    public function post(string $key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }
    
    public function json(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->json;
        }
        return $this->json[$key] ?? $default;
    }
    
    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->json ?? []);
    }
    
    public function only(array $keys): array
    {
        $data = $this->all();
        return array_intersect_key($data, array_flip($keys));
    }
    
    public function getHeader(string $key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }
    
    public function getClientIp(): string
    {
        $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if ($ip = $this->server[$header] ?? null) {
                return $ip;
            }
        }
        return '0.0.0.0';
    }
    
    public function isAjax(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }
    
    public function validate(array $rules): array
    {
        $data = $this->all();
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $rulesList = explode('|', $rule);
            
            foreach ($rulesList as $ruleName) {
                if ($ruleName === 'required' && empty($value)) {
                    $errors[$field][] = "Поле {$field} обязательно для заполнения";
                }
                
                if (str_starts_with($ruleName, 'min:') && strlen($value) < (int)substr($ruleName, 4)) {
                    $errors[$field][] = "Поле {$field} должно содержать минимум " . substr($ruleName, 4) . " символов";
                }
                
                if ($ruleName === 'numeric' && !is_numeric($value)) {
                    $errors[$field][] = "Поле {$field} должно быть числом";
                }
            }
        }
        
        if (!empty($errors)) {
            throw new \Exception(json_encode($errors));
        }
        
        return $data;
    }
}