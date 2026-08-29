<?php
class Device extends Model
{
    protected string $table = 'device';

    public function findByDeviceId(int $id): ?array
    {
        return $this->find($id, 'svp_device_id');
    }

    public function allWithDevice(): array
    {
        $sql = "SELECT dv.svp_device_id, dv.svp_device_name, dv.brand_name, dv.model_name, dv.serial_number, dv.is_active,
                       dt.device_type_name, d.svp_department_name, u.first_name, u.last_name
                FROM device dv
                LEFT JOIN device_type dt ON dt.device_type_id = dv.device_type_id
                LEFT JOIN department d ON d.svp_department_id = dv.svp_department_id
                LEFT JOIN users u ON u.svp_user_id = dv.svp_user_id
                ORDER BY dv.svp_device_name";
        return $this->query($sql)->fetchAll();
    }

    public function update(int $id, array $data, string $pk = 'svp_device_id'): bool
    {
        return parent::update($id, $data, $pk);
    }

    public function delete(int $id, string $pk = 'svp_device_id'): bool
    {
        return parent::delete($id, $pk);
    }
}
