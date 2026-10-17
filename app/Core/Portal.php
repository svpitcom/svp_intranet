<?php
final class Portal
{
    public static function esc($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
    public static function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('Asia/Bangkok')))->format('Y-m-d H:i:s');
    }
    public static function today(): string
    {
        return substr(self::now(), 0, 10);
    }
    public static function manager(array $user): bool
    {
        return in_array($user['user_role'] ?? '', ['admin', 'manager'], true);
    }
    public static function page(int $total, int $requested, int $size = 15): array
    {
        $pages = max(1, (int) ceil($total / $size));
        $page = max(1, min($pages, $requested));
        return ['total' => $total, 'pages' => $pages, 'page' => $page, 'offset' => ($page - 1) * $size, 'size' => $size];
    }
    public static function text(array $data, string $key, int $max, bool $required = true): string
    {
        $value = is_string($data[$key] ?? null) ? trim($data[$key]) : '';
        if (($required && $value === '') || mb_strlen($value) > $max) throw new RuntimeException('กรุณากรอกข้อมูลให้ครบและไม่เกินความยาวที่กำหนด: ' . $key);
        return $value;
    }
    public static function id($value): ?int
    {
        if ($value === null || $value === '') return null;
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) throw new RuntimeException('รหัสรายการไม่ถูกต้อง');
        return $id;
    }
}
