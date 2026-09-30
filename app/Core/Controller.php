<?php
abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data);
        $viewFile = BASE_PATH . "/app/Views/{$view}.php";
        if (!file_exists($viewFile)) {
            http_response_code(500);
            die("View not found: {$view}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require BASE_PATH . "/app/Views/layouts/{$layout}.php";
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . APP_URL . $path);
        exit;
    }

    protected function input(string $key, $default = null)
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? ($key === 'password' ? $value : trim($value)) : $default;
    }

    protected function currentUser(): ?array
    {
        return Session::get('user');
    }
}
