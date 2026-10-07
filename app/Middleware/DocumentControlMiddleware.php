<?php
class DocumentControlMiddleware
{
    public function handle(array $args = []): void
    {
        if (!DocumentControlAccess::allows(Session::get('user'))) {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            exit;
        }
    }
}
