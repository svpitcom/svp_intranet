<?php
class RoleMiddleware
{
    public function handle(array $args = []): void
    {
        $user = Session::get('user');
        if (!$user || !in_array($user['user_role'], $args, true)) {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            exit;
        }
    }
}
