<?php
class Router
{
    private array $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = $uri !== '/' ? rtrim($uri, '/') : $uri;

        foreach ($this->routes as $pattern => $handler) {
            [$routeMethod, $routePath] = explode(' ', $pattern, 2);
            if ($routeMethod !== $method) continue;

            $paramNames = [];
            $regex = preg_replace_callback('#\{(\w+)\}#', function ($m) use (&$paramNames) {
                $paramNames[] = $m[1];
                return $m[1] === 'id' ? '([1-9][0-9]*)' : '([^/]+)';
            }, $routePath);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches);
                $params = array_combine($paramNames, $matches);
                [$controllerName, $action, $middlewares] = array_pad($handler, 3, []);

                foreach ($middlewares as $middleware) {
                    $this->runMiddleware($middleware);
                }
                if ($method === 'POST' && !Session::validCsrf($_POST['_csrf'] ?? null)) {
                    http_response_code(403);
                    echo 'คำขอหมดอายุหรือไม่ถูกต้อง กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง';
                    return;
                }

                if (!class_exists($controllerName)) {
                    http_response_code(500);
                    die("Controller {$controllerName} not found");
                }

                $controller = new $controllerName();
                call_user_func_array([$controller, $action], array_values($params));
                return;
            }
        }

        http_response_code(404);
        require BASE_PATH . '/app/Views/errors/404.php';
    }

    private function runMiddleware(string $middleware): void
    {
        [$name, $args] = array_pad(explode(':', $middleware, 2), 2, '');
        $argList = $args ? explode(',', $args) : [];
        $class = ucfirst($name) . 'Middleware';
        if (!class_exists($class)) die("Middleware {$class} not found");
        (new $class())->handle($argList);
    }
}
