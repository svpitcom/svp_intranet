<?php
final class DocumentControlAccess
{
    /** Call after authentication has refreshed the user from the database. */
    public static function allows(?array $user): bool
    {
        if (!$user || empty($user['is_active'])) return false;
        if (($user['user_role'] ?? '') === 'admin') return true;
        // DCC is department 13; its display name/code may change independently.
        return in_array($user['svp_department_id'] ?? null, [13, '13'], true);
    }
}
