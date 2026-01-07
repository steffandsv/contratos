<?php

namespace App\Core;

class Router
{
    private $routes = [];

    public function add($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch($method, $uri)
    {
        // Strip query string
        $uri = parse_url($uri, PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                return $this->handle($route['handler']);
            }
        }

        http_response_code(404);
        echo "404 Not Found";
    }

    private function handle($handler)
    {
        if (is_array($handler)) {
            [$controller, $action] = $handler;
            $controllerInstance = new $controller();
            return $controllerInstance->$action();
        }

        if (is_callable($handler)) {
            return $handler();
        }
    }
}
