<?php
class DeviceType extends Model
{
    protected string $table = 'device_type';

    // whitelist คอลัมน์ที่ยอมให้ sort ได้เท่านั้น (กัน SQL Injection)
    private const SORTABLE_COLUMNS = ['device_type_id', 'device_type_name'];

    public function find(int $id, string $pk = 'device_type_id'): ?array
    {
        return parent::find($id, $pk);
    }

    public function allSorted(string $sort = 'device_type_id', string $dir = 'asc'): array
    {
        if (!in_array($sort, self::SORTABLE_COLUMNS, true)) {
            $sort = 'device_type_id';
        }
        $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';

        return $this->query("SELECT * FROM {$this->table} ORDER BY {$sort} {$dir}")->fetchAll();
    }

    public function update(int $id, array $data, string $pk = 'device_type_id'): bool
    {
        return parent::update($id, $data, $pk);
    }

    public function delete(int $id, string $pk = 'device_type_id'): bool
    {
        return parent::delete($id, $pk);
    }
}
