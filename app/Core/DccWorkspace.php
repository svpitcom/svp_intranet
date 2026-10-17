<?php
final class DccWorkspace
{
    public static function restricted(?array $user): bool
    {
        return $user !== null && ($user['user_role'] ?? '') !== 'admin'
            && in_array($user['svp_department_id'] ?? null, [13, '13'], true);
    }

    public static function allows(?array $user, string $route): bool
    {
        if (!self::restricted($user)) return true;
        return in_array($route, [
            'GET /', 'GET /login', 'POST /login', 'POST /logout',
            'GET /users', 'GET /users/search',
            'GET /document-control', 'GET /document-control/files',
            'GET /document-control/create', 'POST /document-control/create',
            'GET /document-control/{id}/edit', 'POST /document-control/{id}/edit',
        ], true);
    }
}
