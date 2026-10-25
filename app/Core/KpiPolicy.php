<?php
final class KpiPolicy
{
    public static function allows(?array $user): bool
    {
        return $user !== null && !empty($user['is_active'])
            && (($user['user_role']??'')==='admin' || (int)($user['svp_department_id']??0)>0);
    }
    public static function manages(array $user, int $department): bool
    {
        return self::allows($user) && (($user['user_role'] ?? '') === 'admin'
            || ($department > 0 && (int)($user['svp_department_id'] ?? 0) === $department));
    }
    public static function department(array $user, $requested=null): ?int
    {
        if (!self::allows($user)) throw new RuntimeException('ไม่มีสิทธิ์เข้า KPI');
        $department=Portal::id($requested);
        if (($user['user_role']??'')==='admin') return $department;
        $own=(int)($user['svp_department_id']??0);
        if ($own<=0 || ($department!==null && $department!==$own)) throw new RuntimeException('ดู KPI ได้เฉพาะแผนกของตนเอง');
        return $own;
    }
    public static function reads(array $user, array $row): bool
    {
        return self::allows($user) && ((($user['user_role']??'')==='admin')
            || ((int)($user['svp_department_id'] ?? 0) === (int)$row['department_id']
                && (empty($row['user_id']) || ($user['user_role']??'')==='manager' || (int)$row['user_id'] === (int)$user['svp_user_id'])));
    }
    public static function writes(array $user, array $row): bool
    {
        return self::reads($user,$row) && $row['source'] === 'manual'
            && (self::manages($user,(int)$row['department_id']) || (int)($row['user_id'] ?? 0) === (int)$user['svp_user_id']);
    }
    public static function period(string $value): string
    {
        if (!preg_match('/^(20[0-9]{2})-(0[1-9]|1[0-2])$/D', $value)) throw new RuntimeException('เดือนต้องอยู่ในรูปแบบ YYYY-MM (ค.ศ. 2000–2099)');
        return $value;
    }
    public static function number($value, bool $positive = false): float
    {
        if (!is_scalar($value) || !preg_match('/^\d{1,10}(\.\d{1,4})?$/D', (string)$value)) throw new RuntimeException('ตัวเลขต้องไม่ติดลบ และมีทศนิยมไม่เกิน 4 ตำแหน่ง');
        $number = (float)$value;
        if ($positive && $number <= 0) throw new RuntimeException('เป้าหมายและน้ำหนักต้องมากกว่า 0');
        return $number;
    }
    public static function score(?float $actual, float $target, string $direction): ?float
    {
        if ($actual === null || $target <= 0) return null;
        return round(min(100, $direction === 'lower' ? ($actual == 0 ? 100 : $target / $actual * 100) : $actual / $target * 100), 2);
    }
}
