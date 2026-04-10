<?php

namespace App\Helpers;

class EnvHelper {

    private static array $variables = [];
    private static bool $loaded = false;
    
    public static function load(?string $path = null): void 
    {
        if (self::$loaded) {
            return;
        }
        
        $path = $path ?? __DIR__ . '/../../.env';
        
        if (!file_exists($path)) {
            throw new \Exception(".env file not found at: {$path}");
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }
            
            if (str_contains($line, '=')) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                    $value = substr($value, 1, -1);
                }
                if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                    $value = substr($value, 1, -1);
                }
                
                self::$variables[$key] = $value;

                putenv("{$key}={$value}");

                $_ENV[$key] = $value;
            }
        }
        
        self::$loaded = true;
    }
    
    public static function get(string $key, $default = null) 
    {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$variables[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }
    
    public static function getString(string $key, string $default = ''): string 
    {
        $value = self::get($key, $default);
        return (string)$value;
    }

    public static function getInt(string $key, int $default = 0): int 
    {
        $value = self::get($key, $default);
        return (int)$value;
    }
    
    public static function getFloat(string $key, float $default = 0.0): float 
    {
        $value = self::get($key, $default);
        return (float)$value;
    }
    
    public static function getBool(string $key, bool $default = false): bool 
    {
        $value = self::get($key, $default);
        
        if (is_bool($value)) {
            return $value;
        }
        
        $value = strtolower((string)$value);
        return in_array($value, ['true', '1', 'yes', 'on']);
    }
    
    public static function has(string $key): bool 
    {
        return self::get($key) !== null;
    }
    
    public static function all(): array 
    {
        if (!self::$loaded) {
            self::load();
        }
        return self::$variables;
    }
}