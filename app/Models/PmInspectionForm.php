<?php
final class PmInspectionForm extends Model
{
    protected string $table='pm_inspection_form';
    public function forRecord(int $id): ?array
    {
        $row=$this->query('SELECT payload FROM pm_inspection_form WHERE pm_record_id=?',[$id])->fetch();
        return $row ? json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR) : null;
    }
    public function save(int $id,array $data): void
    {
        $this->insert(['pm_record_id'=>$id,'payload'=>json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    }
}
