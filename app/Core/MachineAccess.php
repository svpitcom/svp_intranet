<?php
final class MachineAccess
{
    public static function manages(?array $user): bool
    {
        return self::allows($user);
    }
    public static function requireAccess(?array $user,bool $manage=false): void
    {
        if ($manage ? !self::manages($user) : !self::allows($user)) throw new RuntimeException('ไม่มีสิทธิ์เข้าถึงงานเครื่องจักร');
    }
    public static function allows(?array $user): bool
    {
        return $user !== null && !empty($user['is_active'])
            && (($user['user_role'] ?? '') === 'admin'
                || in_array($user['svp_department_id'] ?? null, [6, '6'], true));
    }
}
