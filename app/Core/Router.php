<?php
namespace App\Core;

/**
 * Basit yönlendirme sınıfı.
 */
class Router
{
    /**
     * @var array<string, array<int, array{path:string,handler:array,middlewares:array}>>
     */
    private array $routes = [];

    public function get(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function match(string $path, array $handler, array $methods, array $middlewares = []): void
    {
        foreach ($methods as $method) {
            $this->addRoute($method, $path, $handler, $middlewares);
        }
    }

    private function addRoute(string $method, string $path, array $handler, array $middlewares): void
    {
        $this->routes[$method][] = [
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(string $uri, string $method): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $candidates = $this->routes[$method] ?? [];

        foreach ($candidates as $route) {
            $params = $this->matchRoute($route['path'], $path);
            if ($params === null) {
                continue;
            }

            [$class, $action] = $route['handler'];
            $controller = new $class();

            foreach ($route['middlewares'] as $middlewareClass) {
                $middleware = new $middlewareClass();
                if (method_exists($middleware, 'handle')) {
                    $middleware->handle($params);
                }
            }

            call_user_func_array([$controller, $action], array_values($params));
            return;
        }

        http_response_code(404);
        echo 'Sayfa bulunamadı';
    }

    /**
     * @return array<string, string>|null
     */
    private function matchRoute(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z0-9_]+)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#u';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
