<?php
class Department extends Model
{
    protected string $table = 'department';

    public function find(int $id, string $pk = 'svp_department_id'): ?array
    {
        return parent::find($id, $pk);
    }
    public function all(string $orderBy = 'svp_department_name'): array
    {
        return parent::all($orderBy);
    }
    public function update(int $id, array $data, string $pk = 'svp_department_id'): bool
    {
        return parent::update($id, $data, $pk);
    }
    public function delete(int $id, string $pk = 'svp_department_id'): bool
    {
        return parent::delete($id, $pk);
    }
}
