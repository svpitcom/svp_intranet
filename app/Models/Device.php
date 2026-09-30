<?php
class Device extends Model
{
    protected string $table = 'device';

    private const SORTABLE_COLUMNS = [
        'svp_device_id',
        'svp_device_name',
        'brand_name',
        'is_active',
        'device_type_name',
        'svp_department_name',
    ];

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

    /** นับจำนวนอุปกรณ์ทั้งหมด (ใช้คำนวณจำนวนหน้า) */
    public function countAll(string $search = ''): int
    {
        [$where, $params] = $this->searchClause($search);
        return (int) $this->query("SELECT COUNT(*) FROM device dv
            LEFT JOIN device_type dt ON dt.device_type_id = dv.device_type_id
            LEFT JOIN department d ON d.svp_department_id = dv.svp_department_id
            LEFT JOIN users u ON u.svp_user_id = dv.svp_user_id {$where}", $params)->fetchColumn();
    }

    private function searchClause(string $search): array
    {
        $tokens = preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $groups = $params = [];
        $columns = ['dv.svp_device_name', 'dv.brand_name', 'dv.model_name', 'dv.serial_number', 'dt.device_type_name', 'd.svp_department_name', 'u.first_name', 'u.last_name'];
        foreach ($tokens as $tokenIndex => $token) {
            $conditions = [];
            foreach ($columns as $columnIndex => $column) {
                $name = 'search_' . $tokenIndex . '_' . $columnIndex;
                $conditions[] = "{$column} LIKE :{$name} ESCAPE '!'";
                $params[$name] = '%' . strtr($token, ['!'=>'!!', '%'=>'!%', '_'=>'!_']) . '%';
            }
            $groups[] = '(' . implode(' OR ', $conditions) . ')';
        }
        return [$groups ? 'WHERE ' . implode(' AND ', $groups) : '', $params];
    }

    /**
     * ดึงรายการอุปกรณ์แบบแบ่งหน้า พร้อม sort
     * $page เริ่มที่ 1, $perPage = จำนวนแถวต่อหน้า
     */
    public function paginate(int $page = 1, int $perPage = 10, string $sort = 'svp_device_id', string $dir = 'asc', string $search = ''): array
    {
        if (!in_array($sort, self::SORTABLE_COLUMNS, true)) {
            $sort = 'svp_device_id';
        }
        $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';

        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sortColumn = match ($sort) {
            'device_type_name'    => 'dt.device_type_name',
            'svp_department_name' => 'd.svp_department_name',
            default                => "dv.{$sort}",
        };

        [$where, $params] = $this->searchClause($search);
        $sql = "SELECT dv.svp_device_id, dv.svp_device_name, dv.brand_name, dv.model_name, dv.serial_number, dv.is_active,
                       dt.device_type_name, d.svp_department_name, u.first_name, u.last_name
                FROM device dv
                LEFT JOIN device_type dt ON dt.device_type_id = dv.device_type_id
                LEFT JOIN department d ON d.svp_department_id = dv.svp_department_id
                LEFT JOIN users u ON u.svp_user_id = dv.svp_user_id
                {$where}
                ORDER BY {$sortColumn} {$dir}, dv.svp_device_id ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $name => $value) $stmt->bindValue(':' . $name, $value, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
