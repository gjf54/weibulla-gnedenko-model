<?php
namespace App\Core;

class Response
{
    private int $statusCode;
    private array $headers;
    private $content;
    
    public function __construct($content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }
    
    public static function json($data, int $statusCode = 200): self
    {
        return new self(json_encode($data, JSON_UNESCAPED_UNICODE), $statusCode, [
            'Content-Type' => 'application/json'
        ]);
    }
    
    public static function html(string $html, int $statusCode = 200): self
    {
        return new self($html, $statusCode, [
            'Content-Type' => 'text/html; charset=utf-8'
        ]);
    }
    
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, [
            'Location' => $url
        ]);
    }
    
    public static function notFound(string $message = 'Not Found'): self
    {
        return new self($message, 404, [
            'Content-Type' => 'text/plain; charset=utf-8'
        ]);
    }
    
    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self($message, 403, [
            'Content-Type' => 'text/plain; charset=utf-8'
        ]);
    }
    
    public static function serverError(string $message = 'Internal Server Error'): self
    {
        return new self($message, 500, [
            'Content-Type' => 'text/plain; charset=utf-8'
        ]);
    }
    
    public static function file(string $filePath, string $fileName = null): self
    {
        if (!file_exists($filePath)) {
            return self::notFound('File not found');
        }
        
        $fileName = $fileName ?? basename($filePath);
        $mimeType = mime_content_type($filePath);
        
        return new self(file_get_contents($filePath), 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => filesize($filePath)
        ]);
    }
    
    public function send(): void
    {
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }
        
        echo $this->content;
    }
}