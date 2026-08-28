<?php
class Position extends Model
{
    protected string $table = 'position';

    public function find(int $id, string $pk = 'svp_position_id'): ?array
    {
        return parent::find($id, $pk);
    }
    public function all(string $orderBy = 'position_name'): array
    {
        return parent::all($orderBy);
    }
    public function update(int $id, array $data, string $pk = 'svp_position_id'): bool
    {
        return parent::update($id, $data, $pk);
    }
    public function delete(int $id, string $pk = 'svp_position_id'): bool
    {
        return parent::delete($id, $pk);
    }
}
