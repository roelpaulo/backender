<?php

namespace Backender\Controllers;

use Backender\Core\App;
use Backender\Http\Response;
use Backender\Services\Auth;
use Backender\Services\ApiKeyService;

class ApiKeyController
{
    private App $app;
    private Auth $auth;
    private ApiKeyService $apiKeyService;
    
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->auth = new Auth($app->getDatabase());
        $this->apiKeyService = new ApiKeyService($app->getDatabase());
    }
    
    private function view(string $view, array $data = []): Response
    {
        extract($data);
        ob_start();
        include __DIR__ . '/../Views/' . $view . '.php';
        $content = ob_get_clean();
        
        ob_start();
        include __DIR__ . '/../Views/layout.php';
        $html = ob_get_clean();
        
        return Response::html($html);
    }
    
    public function index(): Response
    {
        $this->auth->requireAuth();
        
        $keys = $this->apiKeyService->getAllKeys();
        
        return $this->view('api-keys', [
            'title' => 'API Keys',
            'currentPage' => 'api-keys',
            'showNav' => true,
            'keys' => $keys
        ]);
    }
    
    public function create(): Response
    {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return Response::redirect('/api-keys');
        }
        
        $label = $_POST['label'] ?? '';
        
        if (empty($label)) {
            return Response::redirect('/api-keys?error=' . urlencode('Label is required'));
        }
        
        $result = $this->apiKeyService->generateKey($label);
        
        if ($result['success']) {
            return Response::redirect('/api-keys?key=' . urlencode($result['key']));
        }
        
        return Response::redirect('/api-keys?error=' . urlencode($result['error'] ?? 'Failed to create API key'));
    }
    
    public function delete(): Response
    {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return Response::redirect('/api-keys');
        }
        
        $id = $_POST['id'] ?? 0;
        
        if ($this->apiKeyService->deleteKey((int)$id)) {
            return Response::redirect('/api-keys?success=' . urlencode('API key deleted'));
        }
        
        return Response::redirect('/api-keys?error=' . urlencode('Failed to delete API key'));
    }
}
