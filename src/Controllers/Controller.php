<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;

abstract class Controller
{
    protected Request $request;
    protected Response $response;
    protected View $view;
    
    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response();
        $this->view = new View();
    }
    
    protected function render(string $view, array $data = []): Response
    {
        $content = $this->view->renderWithLayout(view: $view, data: $data);
        return Response::html($content);
    }
    
    protected function json(array $data, int $statusCode = 200): Response
    {
        return Response::json($data, $statusCode);
    }
    
    protected function redirect(string $url): Response
    {
        return Response::redirect($url);
    }
    
    protected function getPostData(): array
    {
        return $this->request->all();
    }
    
    protected function validate(array $rules): array
    {
        return $this->request->validate($rules);
    }
}