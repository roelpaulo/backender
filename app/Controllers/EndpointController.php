<?php

namespace Backender\Controllers;

use Backender\Core\App;
use Backender\Http\Response;
use Backender\Services\Auth;

class EndpointController
{
    private App $app;
    private Auth $auth;
    
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->auth = new Auth($app->getDatabase());
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
    
    public function create(): Response
    {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return Response::redirect('/');
        }
        
        $name = $_POST['name'] ?? '';
        $method = $_POST['method'] ?? 'GET';
        $path = $_POST['path'] ?? '';
        
        if (empty($name) || empty($path)) {
            return Response::redirect('/?error=' . urlencode('Name and path are required'));
        }
        
        if (!str_starts_with($path, '/')) {
            return Response::redirect('/?error=' . urlencode('Path must start with /'));
        }
        
        try {
            $stmt = $this->app->getDatabase()->prepare(
                'INSERT INTO endpoints (name, method, path) VALUES (?, ?, ?)'
            );
            $stmt->execute([$name, strtoupper($method), $path]);
            
            $endpointId = $this->app->getDatabase()->lastInsertId();
            
            // Create default logic file
            $defaultLogic = <<<'PHP'
<?php
return function ($request) {
    return [
        'message' => 'Hello from Backender!',
        'timestamp' => time(),
        'method' => $request->method(),
        'path' => $request->path()
    ];
};
PHP;
            
            $logicPath = $this->app->getStoragePath() . '/endpoints/' . $endpointId . '.php';
            file_put_contents($logicPath, $defaultLogic);
            
            $this->app->log('info', "Endpoint created: {$method} {$path}", $endpointId);
            
            return Response::redirect('/endpoint/edit/' . $endpointId);
            
        } catch (\Exception $e) {
            $this->app->log('error', 'Failed to create endpoint: ' . $e->getMessage());
            return Response::redirect('/?error=' . urlencode('Failed to create endpoint'));
        }
    }
    
    public function edit(int $id): Response
    {
        $this->auth->requireAuth();
        
        $stmt = $this->app->getDatabase()->prepare('SELECT * FROM endpoints WHERE id = ?');
        $stmt->execute([$id]);
        $endpoint = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$endpoint) {
            return Response::redirect('/?error=' . urlencode('Endpoint not found'));
        }
        
        $logicPath = $this->app->getStoragePath() . '/endpoints/' . $id . '.php';
        $logic = file_exists($logicPath) ? file_get_contents($logicPath) : '';
        
        return $this->view('edit', [
            'title' => 'Edit Endpoint',
            'currentPage' => 'dashboard',
            'showNav' => true,
            'endpoint' => $endpoint,
            'logic' => $logic
        ]);
    }
    
    public function update(int $id): Response
    {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return Response::redirect('/endpoint/edit/' . $id);
        }
        
        $name = $_POST['name'] ?? '';
        $method = $_POST['method'] ?? 'GET';
        $path = $_POST['path'] ?? '';
        $requireAuth = isset($_POST['require_auth']) ? 1 : 0;
        
        if (empty($name) || empty($path)) {
            return Response::redirect('/endpoint/edit/' . $id . '?error=' . urlencode('Name and path are required'));
        }
        
        try {
            $stmt = $this->app->getDatabase()->prepare(
                'UPDATE endpoints SET name = ?, method = ?, path = ?, require_auth = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
            );
            $stmt->execute([$name, strtoupper($method), $path, $requireAuth, $id]);
            
            $this->app->log('info', "Endpoint updated: {$method} {$path}", $id);
            
            return Response::redirect('/endpoint/edit/' . $id . '?message=' . urlencode('Endpoint updated'));
            
        } catch (\Exception $e) {
            $this->app->log('error', 'Failed to update endpoint: ' . $e->getMessage(), $id);
            return Response::redirect('/endpoint/edit/' . $id . '?error=' . urlencode('Failed to update endpoint'));
        }
    }
    
    public function updateLogic(int $id): Response
    {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return Response::redirect('/endpoint/edit/' . $id);
        }
        
        $logic = $_POST['logic'] ?? '';
        
        if (empty($logic)) {
            return Response::redirect('/endpoint/edit/' . $id . '?error=' . urlencode('Logic cannot be empty'));
        }
        
        try {
            $logicPath = $this->app->getStoragePath() . '/endpoints/' . $id . '.php';
            file_put_contents($logicPath, $logic);
            
            $this->app->log('info', 'Endpoint logic updated', $id);
            
            return Response::redirect('/endpoint/edit/' . $id . '?message=' . urlencode('Logic saved'));
            
        } catch (\Exception $e) {
            $this->app->log('error', 'Failed to update logic: ' . $e->getMessage(), $id);
            return Response::redirect('/endpoint/edit/' . $id . '?error=' . urlencode('Failed to save logic'));
        }
    }
    
    public function toggle(int $id): Response
    {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return Response::redirect('/');
        }
        
        try {
            $stmt = $this->app->getDatabase()->prepare('SELECT enabled FROM endpoints WHERE id = ?');
            $stmt->execute([$id]);
            $endpoint = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$endpoint) {
                return Response::redirect('/?error=' . urlencode('Endpoint not found'));
            }
            
            $newStatus = $endpoint['enabled'] ? 0 : 1;
            
            $stmt = $this->app->getDatabase()->prepare('UPDATE endpoints SET enabled = ? WHERE id = ?');
            $stmt->execute([$newStatus, $id]);
            
            $this->app->log('info', 'Endpoint ' . ($newStatus ? 'enabled' : 'disabled'), $id);
            
            return Response::redirect('/');
            
        } catch (\Exception $e) {
            $this->app->log('error', 'Failed to toggle endpoint: ' . $e->getMessage(), $id);
            return Response::redirect('/?error=' . urlencode('Failed to toggle endpoint'));
        }
    }
    
    public function delete(int $id): Response
    {
        $this->auth->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return Response::redirect('/');
        }
        
        try {
            $stmt = $this->app->getDatabase()->prepare('DELETE FROM endpoints WHERE id = ?');
            $stmt->execute([$id]);
            
            // Delete logic file
            $logicPath = $this->app->getStoragePath() . '/endpoints/' . $id . '.php';
            if (file_exists($logicPath)) {
                unlink($logicPath);
            }
            
            $this->app->log('info', 'Endpoint deleted', $id);
            
            return Response::redirect('/?message=' . urlencode('Endpoint deleted'));
            
        } catch (\Exception $e) {
            $this->app->log('error', 'Failed to delete endpoint: ' . $e->getMessage(), $id);
            return Response::redirect('/?error=' . urlencode('Failed to delete endpoint'));
        }
    }
}
