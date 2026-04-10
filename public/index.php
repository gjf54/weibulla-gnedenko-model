<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Request;

require_once __DIR__ . '/../routes/web.php';

try {
    $debug = getenv('APP_DEBUG') === 'true' || $_ENV['APP_DEBUG'] === 'true';
    if ($debug) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }
    
    $request = new Request();
    
    $response = Router::dispatch($request);
    
    $response->send();
    
} catch (Exception $e) {
    $debug = getenv('APP_DEBUG') === 'true' || $_ENV['APP_DEBUG'] === 'true';
    
    if ($debug) {
        echo "<h1>Error</h1>";
        echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
        echo "<h3>Stack Trace:</h3>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    } else {
        http_response_code(500);
        echo "Internal Server Error";
    }
}
