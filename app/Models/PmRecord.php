<?php
class PmRecord extends Model
{
    protected string $table = 'pm_record';

    public function find(int $id, string $pk = 'pm_record_id'): ?array
    {
        return parent::find($id, $pk);
    }

    /** ประวัติการทำ PM ของแผนหนึ่งๆ เรียงล่าสุดก่อน */
    public function forSchedule(int $scheduleId): array
    {
        $sql = "SELECT r.*, u.first_name, u.last_name
                FROM pm_record r
                JOIN users u ON u.svp_user_id = r.performed_by
                WHERE r.pm_schedule_id = :id
                ORDER BY r.performed_date DESC, r.pm_record_id DESC";
        return $this->query($sql, ['id' => $scheduleId])->fetchAll();
    }

    /** ประวัติ PM ทั้งหมดในระบบ (สำหรับหน้ารายงาน) */
    public function allWithRelations(): array
    {
        $sql = "SELECT r.*, ps.pm_title, dv.svp_device_name, u.first_name, u.last_name
                FROM pm_record r
                JOIN pm_schedule ps ON ps.pm_schedule_id = r.pm_schedule_id
                JOIN device dv ON dv.svp_device_id = ps.svp_device_id
                JOIN users u ON u.svp_user_id = r.performed_by
                ORDER BY r.performed_date DESC, r.pm_record_id DESC";
        return $this->query($sql)->fetchAll();
    }
}
