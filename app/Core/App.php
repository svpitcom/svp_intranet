<?php
class App
{
    public static function run(): void
    {
        Session::start();

        $routes = require BASE_PATH . '/app/Config/routes.php';
        $router = new Router($routes);

        // ตัด subfolder ออกจาก URI เช่น /svp_intranet/public -> ทำให้เหลือแค่ /login
        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        if (!is_string($uri)) {
            http_response_code(400);
            return;
        }
        if ($basePath !== '' && ($uri === $basePath || str_starts_with($uri, $basePath . '/'))) {
            $uri = substr($uri, strlen($basePath));
        }
        if ($uri === '') {
            $uri = '/';
        }

        $router->dispatch($_SERVER['REQUEST_METHOD'], $uri);
    }
}
