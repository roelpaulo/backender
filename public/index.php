<?php

// Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Autoloader for app classes
spl_autoload_register(function ($class) {
    $prefix = 'Backender\\';
    $baseDir = __DIR__ . '/../app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

use Backender\Core\App;
use Backender\Core\Router;
use Backender\Core\EndpointRunner;
use Backender\Http\Request;
use Backender\Http\Response;
use Backender\Controllers\AdminController;
use Backender\Controllers\EndpointController;
use Backender\Controllers\ApiKeyController;

// Initialize the application
$app = new App();
$router = $app->getRouter();
$router->setDatabase($app->getDatabase());
$router->setStoragePath($app->getStoragePath());

// Create request
$request = new Request();

// Admin routes
$adminRoutes = [
    'GET /setup' => 'setup',
    'POST /setup' => 'setup',
    'GET /login' => 'login',
    'POST /login' => 'login',
    'GET /logout' => 'logout',
    'GET /verify' => 'verify',
    'GET /forgot-password' => 'forgotPassword',
    'POST /forgot-password' => 'forgotPassword',
    'GET /reset-password' => 'resetPassword',
    'POST /reset-password' => 'resetPassword',
    'GET /' => 'dashboard',
    'GET /logs' => 'logs',
];

$apiKeyRoutes = [
    'GET /api-keys' => 'index',
    'POST /api-key/create' => 'create',
    'POST /api-key/delete' => 'delete',
];

$endpointRoutes = [
    'POST /endpoint/create' => 'create',
    'GET /endpoint/edit' => 'edit',
    'POST /endpoint/update' => 'update',
    'POST /endpoint/update-logic' => 'updateLogic',
    'POST /endpoint/toggle' => 'toggle',
    'POST /endpoint/delete' => 'delete',
];

// Check if it's an admin route
$routeKey = $request->method() . ' ' . $request->path();
$pathParts = explode('/', trim($request->path(), '/'));

try {
    // First run setup check
    if ($app->isFirstRun() && $request->path() !== '/setup') {
        Response::redirect('/setup')->send();
        exit;
    }
    
    // Handle admin routes
    if (isset($adminRoutes[$routeKey])) {
        $controller = new AdminController($app);
        $method = $adminRoutes[$routeKey];
        $response = $controller->$method();
        $response->send();
        exit;
    }
    
    // Handle API key routes
    if (isset($apiKeyRoutes[$routeKey])) {
        $controller = new ApiKeyController($app);
        $method = $apiKeyRoutes[$routeKey];
        $response = $controller->$method();
        $response->send();
        exit;
    }
    
    // Handle endpoint management routes
    if (count($pathParts) >= 2 && $pathParts[0] === 'endpoint') {
        $controller = new EndpointController($app);
        
        if ($pathParts[1] === 'create' && $request->method() === 'POST') {
            $response = $controller->create();
        } elseif ($pathParts[1] === 'edit' && isset($pathParts[2]) && $request->method() === 'GET') {
            $response = $controller->edit((int)$pathParts[2]);
        } elseif ($pathParts[1] === 'update' && isset($pathParts[2]) && $request->method() === 'POST') {
            $response = $controller->update((int)$pathParts[2]);
        } elseif ($pathParts[1] === 'update-logic' && isset($pathParts[2]) && $request->method() === 'POST') {
            $response = $controller->updateLogic((int)$pathParts[2]);
        } elseif ($pathParts[1] === 'toggle' && isset($pathParts[2]) && $request->method() === 'POST') {
            $response = $controller->toggle((int)$pathParts[2]);
        } elseif ($pathParts[1] === 'delete' && isset($pathParts[2]) && $request->method() === 'POST') {
            $response = $controller->delete((int)$pathParts[2]);
        } else {
            $response = Response::json(['error' => 'Not found'], 404);
        }
        
        $response->send();
        exit;
    }
    
    // Handle preflight OPTIONS request for CORS globally
    if ($request->method() === 'OPTIONS') {
        Response::text('', 204)
            ->withCors()
            ->send();
        exit;
    }
    
    // Try to match custom endpoint
    $endpoint = $router->match($request);
    
    if ($endpoint) {
        $logicPath = $router->getEndpointLogicPath($endpoint['id']);
        $requireAuth = (bool)($endpoint['require_auth'] ?? false);
        $runner = new EndpointRunner($logicPath, $app->getDatabase(), $requireAuth);
        $response = $runner->execute($request);
        
        $app->log('request', "{$request->method()} {$request->path()}", $endpoint['id']);
        
        $response->send();
        exit;
    }
    
    // No route matched
    Response::json(['error' => 'Not found'], 404)->send();
    
} catch (\Throwable $e) {
    error_log("Fatal error: " . $e->getMessage());
    $app->log('error', $e->getMessage());
    Response::json([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], 500)->send();
}
