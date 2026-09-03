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
                       u.first_name, u.last_name,
                       DATEDIFF(ps.next_pm_date, CURDATE()) AS days_remaining
                FROM pm_schedule ps
                JOIN device dv ON dv.svp_device_id = ps.svp_device_id
                LEFT JOIN users u ON u.svp_user_id = ps.responsible_user_id
                WHERE ps.is_active = 1
                ORDER BY ps.next_pm_date ASC";
        return $this->query($sql)->fetchAll();
    }

    public function findWithRelations(int $id): ?array
    {
        $sql = "SELECT ps.*, dv.svp_device_name, u.first_name, u.last_name
                FROM pm_schedule ps
                JOIN device dv ON dv.svp_device_id = ps.svp_device_id
                LEFT JOIN users u ON u.svp_user_id = ps.responsible_user_id
                WHERE ps.pm_schedule_id = :id
                LIMIT 1";
        $row = $this->query($sql, ['id' => $id])->fetch();
        return $row ?: null;
    }

    /** อัปเดตวันที่ทำ PM ล่าสุด + คำนวณวันครบกำหนดครั้งถัดไปอัตโนมัติ */
    public function markCompleted(int $id, string $performedDate): bool
    {
        $schedule = $this->find($id);
        if (!$schedule) return false;

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
