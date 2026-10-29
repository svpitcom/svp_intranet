<?php
final class Machine extends Model
{
    protected string $table = 'machines';
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? MachineDatabase::connect();
    }
    public static function person(?int $id, bool $machineAccess = false): string
    {
        if (!$id) return '';
        $row = (new User())->findById($id);
        if (!$row || !$row['is_active']) throw new RuntimeException('ผู้รับผิดชอบไม่พร้อมใช้งาน');
        if ($machineAccess && !MachineAccess::allows($row)) throw new RuntimeException('ผู้รับผิดชอบ PM ต้องมีสิทธิ์ใช้งานระบบเครื่องจักร');
        return trim($row['first_name'] . ' ' . $row['last_name']);
    }
    public static function actor(array $user): string
    {
        return trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    }
    public function listing(array $user, string $q = '', int $page = 1): array
    {
        MachineAccess::requireAccess($user);
        $q = mb_substr($q, 0, 200);
        $where = $q !== '' ? ' WHERE code LIKE ? OR name LIKE ? OR location LIKE ? OR serial_number LIKE ?' : '';
        $params = $q !== '' ? array_fill(0, 4, '%' . $q . '%') : [];
        $paging = Portal::page((int)$this->query('SELECT COUNT(*) FROM machines' . $where, $params)->fetchColumn(), $page);
        $rows = $this->query('SELECT * FROM machines' . $where . ' ORDER BY code,id LIMIT ' . $paging['size'] . ' OFFSET ' . $paging['offset'], $params)->fetchAll();
        return $paging + ['rows' => $rows];
    }
    public function detail(int $id, array $user): array
    {
        MachineAccess::requireAccess($user);
        $row = $this->find($id);
        if (!$row) throw new RuntimeException('ไม่พบเครื่องจักร');
        return $row;
    }
    public function choices(array $user): array
    {
        MachineAccess::requireAccess($user);
        return $this->query('SELECT id,code,name FROM machines WHERE is_active=1 ORDER BY code')->fetchAll();
    }
    public function event(string $type, int $id, array $user, array $payload): void
    {
        $this->query('INSERT INTO machine_events(entity_type,entity_id,actor_id,actor_name,payload,created_at) VALUES(?,?,?,?,?,?)', [$type, $id, $user['svp_user_id'], self::actor($user), json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), Portal::now()]);
    }
    public function save(?int $id, array $input, array $user): int
    {
        MachineAccess::requireAccess($user, true);
        $data = [];
        foreach (['code' => 80, 'name' => 200, 'machine_type' => 120, 'brand' => 120, 'model' => 120, 'serial_number' => 150, 'location' => 200] as $key => $max) $data[$key] = Portal::text($input, $key, $max, in_array($key, ['code', 'name'], true));
        $data['department_id'] = Portal::id($input['department_id'] ?? '');
        $dept = $data['department_id'] ? (new Department())->find($data['department_id']) : null;
        if ($data['department_id'] && !$dept) throw new RuntimeException('ไม่พบแผนก');
        $data['department_name'] = $dept['svp_department_name'] ?? '';
        $data['owner_id'] = Portal::id($input['owner_id'] ?? '');
        $data['owner_name'] = self::person($data['owner_id']);
        $data['is_active'] = ($input['is_active'] ?? '') === '1' ? 1 : 0;
        $this->db->beginTransaction();
        try {
            $previous = $id ? $this->query('SELECT * FROM machines WHERE id=? FOR UPDATE', [$id])->fetch() : null;
            if ($id && (!$previous || (string)$previous['version'] !== ($input['version'] ?? null))) throw new RuntimeException('ข้อมูลเครื่องจักรเปลี่ยนแล้ว กรุณาเปิดหน้าใหม่ก่อนบันทึก');
            if ($this->query('SELECT id FROM machines WHERE code=? AND id<>?', [$data['code'], $id ?? 0])->fetch()) throw new RuntimeException('รหัสเครื่องจักรนี้มีแล้ว');
            $data['version'] = $previous ? (int)$previous['version'] + 1 : 1;
            $data['updated_at'] = Portal::now();
            if ($id) parent::update($id, $data);
            else $id = $this->insert($data + ['created_at' => Portal::now()]);
            $this->event('machine', $id, $user, $data);
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
}
