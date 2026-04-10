<?php

namespace App\Core;

class Router
{
    private static array $routes = [];
    private static array $groups = [];
    private static string $currentGroup = '';
    private static array $groupMiddleware = [];
    
    public static function get(string $path, $handler, array $middleware = []): void
    {
        self::addRoute('GET', $path, $handler, $middleware);
    }
    
    public static function post(string $path, $handler, array $middleware = []): void
    {
        self::addRoute('POST', $path, $handler, $middleware);
    }
    
    public static function put(string $path, $handler, array $middleware = []): void
    {
        self::addRoute('PUT', $path, $handler, $middleware);
    }
    
    public static function delete(string $path, $handler, array $middleware = []): void
    {
        self::addRoute('DELETE', $path, $handler, $middleware);
    }
    
    public static function any(string $path, $handler, array $middleware = []): void
    {
        self::addRoute('*', $path, $handler, $middleware);
    }
    
    private static function addRoute(string $method, string $path, $handler, array $middleware = []): void
    {
        if (self::$currentGroup) {
            $path = rtrim(self::$currentGroup, '/') . '/' . ltrim($path, '/');
            $middleware = array_merge(self::$groupMiddleware, $middleware);
        }
        
        $path = self::normalizePath($path);
        
        $pattern = self::pathToRegex($path);
        
        self::$routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
            'params' => []
        ];
    }
    
    private static function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        
        if ($path === '') {
            $path = '/';
        }
        
        return $path;
    }
    
    public static function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $previousGroup = self::$currentGroup;
        $previousMiddleware = self::$groupMiddleware;
        
        self::$currentGroup = self::normalizePath($prefix);
        self::$groupMiddleware = array_merge(self::$groupMiddleware, $middleware);
        
        $callback();
        
        self::$currentGroup = $previousGroup;
        self::$groupMiddleware = $previousMiddleware;
    }
    
    private static function pathToRegex(string $path): string
    {
        $pattern = preg_quote($path, '#');
        
        $pattern = preg_replace_callback('/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}/', function($matches) {
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $pattern);
        
        $pattern = preg_replace_callback('/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\?\\\}/', function($matches) {
            return '(?P<' . $matches[1] . '>[^/]*)';
        }, $pattern);
        
        return '#^' . $pattern . '$#';
    }
    
    public static function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = $request->getPath();
        
        $uri = self::normalizePath($uri);
        
        if (getenv('APP_DEBUG') === 'true') {
            error_log("Dispatching: {$method} {$uri}");
        }
        
        foreach (self::$routes as $route) {
            if ($route['method'] !== '*' && $route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
                
                try {
                    foreach ($route['middleware'] as $middleware) {
                        $middlewareInstance = self::resolveMiddleware($middleware);
                        $response = $middlewareInstance->handle($request, function($req) use ($route, $params) {
                            return self::executeHandler($route['handler'], $req, $params);
                        });
                        
                        if ($response instanceof Response) {
                            return $response;
                        }
                    }
                } catch (\Exception $e) {
                    return Response::serverError('Middleware error: ' . $e->getMessage());
                }
                
                return self::executeHandler($route['handler'], $request, $params);
            }
        }
        
        if (getenv('APP_DEBUG') === 'true') {
            $routes = array_map(function($route) {
                return $route['method'] . ' ' . $route['path'];
            }, self::$routes);
            
            error_log("Route not found: {$method} {$uri}");
            error_log("Available routes: " . implode(', ', $routes));
        }
        
        return Response::notFound('Страница не найдена: ' . $uri);
    }
    
    private static function executeHandler($handler, Request $request, array $params): Response
    {
        try {
            if (is_callable($handler)) {
                $result = call_user_func($handler, $request, $params);
                return $result instanceof Response ? $result : Response::json($result);
            }
            
            if (is_string($handler) && str_contains($handler, '@')) {
                list($controllerClass, $action) = explode('@', $handler);
                
                if (!str_starts_with($controllerClass, 'App\\Controllers\\') && 
                    !str_starts_with($controllerClass, 'App\\')) {
                    $controllerClass = 'App\\Controllers\\' . $controllerClass;
                }
                
                if (!class_exists($controllerClass)) {
                    return Response::notFound("Controller {$controllerClass} not found");
                }
                
                $controller = new $controllerClass();
                
                if (!method_exists($controller, $action)) {
                    return Response::notFound("Action {$action} not found in {$controllerClass}");
                }
                
                $result = $controller->$action($request, $params);
                return $result instanceof Response ? $result : Response::json($result);
            }
            
            return Response::serverError('Invalid route handler');
        } catch (\Exception $e) {
            if (getenv('APP_DEBUG') === 'true') {
                return Response::serverError('Handler error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            }
            return Response::serverError('Handler error occurred');
        }
    }
    
    private static function resolveMiddleware(string $middleware)
    {
        if (!str_starts_with($middleware, 'App\\Middleware\\') && 
            !str_starts_with($middleware, 'App\\')) {
            $middleware = 'App\\Middleware\\' . $middleware;
        }
        
        if (class_exists($middleware)) {
            return new $middleware();
        }
        
        throw new \Exception("Middleware {$middleware} not found");
    }
    
    public static function debug(): void
    {
        echo "<h3>Registered Routes</h3>";
        echo "<pre>";
        foreach (self::$routes as $route) {
            echo $route['method'] . " " . $route['path'] . "\n";
            echo "  Pattern: " . $route['pattern'] . "\n";
            echo "  Handler: " . (is_string($route['handler']) ? $route['handler'] : 'Closure') . "\n\n";
        }
        echo "</pre>";
    }
    
    public static function getRoutes(): array
    {
        return self::$routes;
    }
}