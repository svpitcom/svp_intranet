<?php
class PmSchedule extends Model
{
    protected string $table = 'pm_schedule';

    public function find(int $id, string $pk = 'pm_schedule_id'): ?array
    {
        return parent::find($id, $pk);
    }

    public function update(int $id, array $data, string $pk = 'pm_schedule_id'): bool
    {
        return parent::update($id, $data, $pk);
    }

    public function delete(int $id, string $pk = 'pm_schedule_id'): bool
    {
        return parent::delete($id, $pk);
    }

    /** รายการแผน PM ทั้งหมด พร้อมชื่ออุปกรณ์และผู้รับผิดชอบ เรียงตามวันครบกำหนดใกล้สุดก่อน */
    public function allWithRelations(): array
    {
        $sql = "SELECT ps.*, dv.svp_device_name, dv.serial_number,
                       u.first_name, u.last_name, done.completed_current_year,
                       DATEDIFF(ps.next_pm_date, CURDATE()) AS days_remaining
                FROM pm_schedule ps
                JOIN device dv ON dv.svp_device_id = ps.svp_device_id
                LEFT JOIN users u ON u.svp_user_id = ps.responsible_user_id
                LEFT JOIN (
                    SELECT pm_schedule_id, MAX(performed_date) AS completed_current_year
                    FROM pm_record
                    WHERE result_status = 'completed' AND YEAR(performed_date) = YEAR(CURDATE())
                    GROUP BY pm_schedule_id
                ) done ON done.pm_schedule_id = ps.pm_schedule_id
                WHERE ps.is_active = 1
                ORDER BY ps.next_pm_date ASC";
        return $this->query($sql)->fetchAll();
    }

    /** แสดงแผนของปีปัจจุบันและวันครบรอบปีหน้าโดยไม่สร้างประวัติการทำ PM ล่วงหน้า */
    public static function withAnnualDates(array $schedules, ?int $year = null): array
    {
        $year ??= (int) date('Y');
        foreach ($schedules as &$schedule) {
            $due = $schedule['next_pm_date'] ?? '';
            $dueYear = is_string($due) && Validation::date($due) ? (int) substr($due, 0, 4) : 0;
            $completed = $schedule['completed_current_year'] ?? null;
            $schedule['plan_current_year'] = null;
            $schedule['plan_next_year'] = null;
            $schedule['plan_current_done'] = false;
            $schedule['plan_next_projected'] = false;

            if ($dueYear === $year) {
                $schedule['plan_current_year'] = $due;
            } elseif ($dueYear === $year + 1) {
                if ($completed) {
                    $schedule['plan_current_year'] = $completed;
                    $schedule['plan_current_done'] = true;
                }
            }
            $frequency = (int)($schedule['frequency_days'] ?? 0);
            if ($dueYear && $frequency > 0 && $dueYear <= $year + 1) {
                $base = new DateTimeImmutable($due);
                $start = new DateTimeImmutable(($year + 1) . '-01-01');
                $days = $base < $start ? (int)$base->diff($start)->days : 0;
                $steps = (int)ceil($days / $frequency);
                $projected = $base->modify('+' . ($steps * $frequency) . ' days')->format('Y-m-d');
                if ((int)substr($projected, 0, 4) === $year + 1) {
                    $schedule['plan_next_year'] = $projected;
                    $schedule['plan_next_projected'] = $projected !== $due;
                }
            }
        }
        unset($schedule);
        return $schedules;
    }

    public function findWithRelations(int $id): ?array
    {
        $sql = "SELECT ps.*, dv.svp_device_name, dv.serial_number, dv.asset_group, d.svp_department_name, u.first_name, u.last_name
                FROM pm_schedule ps
                JOIN device dv ON dv.svp_device_id = ps.svp_device_id
                LEFT JOIN department d ON d.svp_department_id = dv.svp_department_id
                LEFT JOIN users u ON u.svp_user_id = ps.responsible_user_id
                WHERE ps.pm_schedule_id = :id
                LIMIT 1";
        $row = $this->query($sql, ['id' => $id])->fetch();
        return $row ?: null;
    }

    /** Include inactive plans and retain plans whose related records are missing. */
    public function allForSharePoint(): array
    {
        return $this->query('SELECT ps.*, dv.svp_device_name, dv.serial_number, u.first_name, u.last_name
            FROM pm_schedule ps
            LEFT JOIN device dv ON dv.svp_device_id = ps.svp_device_id
            LEFT JOIN users u ON u.svp_user_id = ps.responsible_user_id
            ORDER BY ps.pm_schedule_id')->fetchAll();
    }

    /** อัปเดตวันที่ทำ PM ล่าสุด + คำนวณวันครบกำหนดครั้งถัดไปอัตโนมัติ */
    public function markCompleted(int $id, string $performedDate): bool
    {
        $schedule = $this->find($id);
        if (!$schedule) return false;
        if (!empty($schedule['last_pm_date']) && $performedDate < $schedule['last_pm_date']) return true;

        $nextDate = date('Y-m-d', strtotime($performedDate . " +{$schedule['frequency_days']} days"));

        return $this->update($id, [
            'last_pm_date' => $performedDate,
            'next_pm_date' => $nextDate,
        ]);
    }

    /** สถานะสำหรับแสดง badge: overdue / soon (ภายใน 7 วัน) / normal */
    public static function statusFromDaysRemaining(?int $daysRemaining): string
    {
        if ($daysRemaining === null) return 'normal';
        if ($daysRemaining < 0) return 'overdue';
        if ($daysRemaining <= 7) return 'soon';
        return 'normal';
    }
}
