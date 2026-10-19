<?php
final class MachineAccess
{
    public static function allows(?array $user): bool
    {
        return $user !== null && !empty($user['is_active'])
            && (($user['user_role'] ?? '') === 'admin'
                || in_array($user['svp_department_id'] ?? null, [6, '6'], true));
    }
}
