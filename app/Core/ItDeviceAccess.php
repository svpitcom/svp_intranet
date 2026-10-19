<?php
final class ItDeviceAccess
{
    public static function allows(?array $user): bool
    {
        return $user !== null && !empty($user['is_active'])
            && (($user['user_role'] ?? '') === 'admin'
                || in_array($user['svp_department_id'] ?? null, [2, '2'], true));
    }
}
