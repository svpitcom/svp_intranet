<?php
class Dashboard extends Model
{
    public function overview(string $today): array
    {
        $until = (new DateTimeImmutable($today))->modify('+6 days')->format('Y-m-d');
        $stats = $this->query("SELECT
            (SELECT COUNT(*) FROM users WHERE is_active = 1) AS users,
            (SELECT COUNT(*) FROM device WHERE is_active = 1) AS devices,
            (SELECT COUNT(*) FROM pm_schedule WHERE is_active = 1 AND next_pm_date < :today) AS overdue,
            (SELECT COUNT(*) FROM pm_schedule WHERE is_active = 1 AND next_pm_date >= :start AND next_pm_date <= :until) AS upcoming",
            ['today' => $today, 'start' => $today, 'until' => $until])->fetch();
        $schedules = $this->query("SELECT ps.pm_schedule_id, ps.pm_title, ps.next_pm_date,
                dv.svp_device_name, u.first_name, u.last_name
            FROM pm_schedule ps
            LEFT JOIN device dv ON dv.svp_device_id = ps.svp_device_id
            LEFT JOIN users u ON u.svp_user_id = ps.responsible_user_id
            WHERE ps.is_active = 1 AND ps.next_pm_date <= :until
            ORDER BY ps.next_pm_date, ps.pm_schedule_id LIMIT 6", ['until' => $until])->fetchAll();
        return ['stats' => array_map('intval', $stats), 'schedules' => $schedules];
    }
}
