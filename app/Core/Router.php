<?php
declare(strict_types=1);

namespace App\Core;

class Router {
    private array $routes = [];

    public function get(string $pattern, $action): void {
        $this->addRoute('GET', $pattern, $action);
    }

    public function post(string $pattern, $action): void {
        $this->addRoute('POST', $pattern, $action);
    }

    public function put(string $pattern, $action): void {
        $this->addRoute('PUT', $pattern, $action);
    }

    public function delete(string $pattern, $action): void {
        $this->addRoute('DELETE', $pattern, $action);
    }

    public function view(string $pattern, string $viewPath, array $data = []): void {
        $this->get($pattern, function() use ($viewPath, $data) {
            extract($data);
            $fullPath = dirname(__DIR__, 2) . '/views/' . ltrim($viewPath, '/');
            if (file_exists($fullPath)) {
                require $fullPath;
            } else {
                http_response_code(404);
                echo "<h1>Vista no encontrada</h1><p>" . htmlspecialchars($viewPath) . "</p>";
            }
        });
    }

    private function addRoute(string $method, string $pattern, $action): void {
        // Convierte {id} o {param} en expresiones regulares para enteros o texto seguro
        $regex = preg_replace_callback('/\{([a-zA-Z0-9_]+)(?::([^\}]+))?\}/', function ($matches) {
            $name = $matches[1];
            $pattern = $matches[2] ?? '[a-zA-Z0-9_\-\.]+';
            return '(?P<' . $name . '>' . $pattern . ')';
        }, $pattern);

        $regex = '#^' . trim($regex, '/') . '$#';
        $this->routes[strtoupper($method)][$regex] = $action;
    }

    public function dispatch(string $uri, string $method): void {
        $path = trim((string)strtok($uri, '?'), '/');
        $method = strtoupper($method);

        foreach ($this->routes[$method] ?? [] as $pattern => $action) {
            if (preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $cleanParams = array_map(function($param) {
                    return ctype_digit($param) ? (int)$param : $param;
                }, $params);

                if (is_callable($action)) {
                    call_user_func_array($action, array_values($cleanParams));
                    return;
                }

                if (is_array($action)) {
                    [$class, $methodName] = $action;
                    $controller = new $class();
                    call_user_func_array([$controller, $methodName], array_values($cleanParams));
                    return;
                }
            }
        }

        http_response_code(404);
        if (str_starts_with($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || str_starts_with($path, 'api/')) {
            header('Content-Type: application/json');
            echo json_encode(["status" => "error", "message" => "Recurso no encontrado: /{$path}"]);
        } else {
            echo "<h1>404 No Encontrado</h1><p>La ruta solicitada <code>/{$path}</code> no existe en el sistema.</p>";
        }
    }
}