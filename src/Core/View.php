<?php
namespace App\Core;

class View
{
    private string $viewsPath;
    private string $layoutPath;
    private array $sharedData = [];
    
    public function __construct(?string $viewsPath = null, ?string $layoutPath = null)
    {
        $this->viewsPath = $viewsPath ?? __DIR__ . '/../../views/';
        $this->layoutPath = $layoutPath ?? $this->viewsPath . 'layouts/';
    }
    
    public function share(string $key, $value): void
    {
        $this->sharedData[$key] = $value;
    }
    
    public function render(string $view, array $data = []): string
    {
        $data = array_merge($this->sharedData, $data);
        $viewPath = $this->viewsPath . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View {$view} not found at {$viewPath}");
        }
        
        ob_start();
        extract($data);
        require $viewPath;
        $content = ob_get_clean();
        
        return $content;
    }
    
    public function renderWithLayout(string $view, string $layout = 'main', array $data = []): string
    {
        $content = $this->render($view, $data);
        $layoutPath = $this->layoutPath . $layout . '.php';
        
        if (!file_exists($layoutPath)) {
            return $content;
        }
        
        ob_start();
        extract(['content' => $content, 'data' => $data]);
        require $layoutPath;
        return ob_get_clean();
    }
    
    public function include(string $partial, array $data = []): void
    {
        echo $this->render('partials.' . $partial, $data);
    }
    
    public function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}