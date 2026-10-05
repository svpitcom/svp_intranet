<?php
/** Explicit export columns: never export arbitrary database fields. */
class SharePointTables
{
    public const LABELS = [
        'devices' => 'ทะเบียนอุปกรณ์',
        'device_types' => 'ประเภทอุปกรณ์',
        'departments' => 'แผนก',
        'pm_schedules' => 'แผน PM',
    ];

    private const COLUMNS = [
        'devices' => ['svp_device_id' => 'รหัสอุปกรณ์', 'svp_device_name' => 'ชื่ออุปกรณ์', 'brand_name' => 'ยี่ห้อ', 'model_name' => 'รุ่น', 'serial_number' => 'Serial Number', 'device_type_name' => 'ประเภท', 'svp_department_name' => 'แผนก', 'first_name' => 'ชื่อผู้รับผิดชอบ', 'last_name' => 'นามสกุล', 'is_active' => 'ใช้งาน (1/0)'],
        'device_types' => ['device_type_id' => 'รหัสประเภท', 'device_type_name' => 'ชื่อประเภทอุปกรณ์'],
        'departments' => ['svp_department_id' => 'รหัสแผนก', 'svp_code_department' => 'โค้ดแผนก', 'svp_department_name' => 'ชื่อแผนก'],
        'pm_schedules' => ['pm_schedule_id' => 'รหัสแผน PM', 'pm_title' => 'ชื่อแผน', 'svp_device_id' => 'รหัสอุปกรณ์', 'svp_device_name' => 'อุปกรณ์', 'serial_number' => 'Serial Number', 'frequency_days' => 'ความถี่ (วัน)', 'last_pm_date' => 'ทำล่าสุด', 'next_pm_date' => 'กำหนดครั้งถัดไป', 'first_name' => 'ชื่อผู้รับผิดชอบ', 'last_name' => 'นามสกุล', 'checklist' => 'รายการตรวจเช็ค', 'is_active' => 'ใช้งาน (1/0)'],
    ];

    public static function rows(string $type): array
    {
        return match ($type) {
            'devices' => (new Device())->allWithDevice(),
            'device_types' => (new DeviceType())->allSorted(),
            'departments' => (new Department())->all(),
            'pm_schedules' => (new PmSchedule())->allForSharePoint(),
            default => throw new InvalidArgumentException('ประเภทข้อมูลส่งออกไม่ถูกต้อง'),
        };
    }

    public static function csv(string $type, array $rows): string
    {
        $columns = self::COLUMNS[$type] ?? null;
        if ($columns === null) throw new InvalidArgumentException('ประเภทข้อมูลส่งออกไม่ถูกต้อง');
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) throw new RuntimeException('สร้างไฟล์รายงานไม่สำเร็จ');
        try {
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, array_values($columns), ',', '"', '');
            foreach ($rows as $row) {
                $values = [];
                foreach ($columns as $key => $label) {
                    $text = (string) ($row[$key] ?? '');
                    $values[] = preg_match('/^[\x00-\x20]*[=+@-]|^[\t\r\n]/u', $text) ? "'" . $text : $text;
                }
                fputcsv($stream, $values, ',', '"', '');
                if (ftell($stream) > 10 * 1024 * 1024) throw new RuntimeException('รายงานเกินขนาด 10 MB ที่ระบบรองรับ');
            }
            rewind($stream);
            return stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
    }
}
