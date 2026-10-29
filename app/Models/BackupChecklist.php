<?php

/** IT-F-004 monthly backup log. Empty cells are unrecorded, never assumed successful. */
class BackupChecklist extends Model
{
    protected string $table = 'backup_checklists';
    public const ROW_FIELDS = ['data' => 'Data', 'data_full' => 'Data Full', 'start' => 'Start', 'end' => 'End', 'status' => 'Status', 'checked_by' => 'ผู้ตรวจเช็ก', 'notes' => 'หมายเหตุ'];
    public const TITLE = 'Operation Daily Incremental Checklist Backup Server';

    public static function authorize(?array $user): void
    {
        if (!ItDeviceAccess::allows($user)) throw new RuntimeException('เฉพาะฝ่าย IT และผู้ดูแลระบบ');
    }

    public static function month($value): string
    {
        if (!is_string($value) || !preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/D', $value)) throw new RuntimeException('กรุณาเลือกเดือนและปี ค.ศ. 2000–2099');
        return $value;
    }

    public static function days(string $month): int
    {
        return (int)(new DateTimeImmutable(self::month($month) . '-01'))->format('t');
    }

    public static function clean(array $input): array
    {
        $data = ['month' => self::month($input['month'] ?? '')];
        foreach (['location' => 120, 'server_name' => 120, 'prepared_by' => 120, 'approved_by' => 120] as $key => $max) {
            $data[$key] = self::text($input, $key, $max, in_array($key, ['location', 'server_name'], true));
        }
        foreach (['prepared_date', 'approved_date'] as $key) {
            $data[$key] = self::text($input, $key, 10);
            if ($data[$key] !== '') {
                $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data[$key]);
                if (!$date || $date->format('Y-m-d') !== $data[$key] || !preg_match('/^20\d{2}-/', $data[$key])) throw new RuntimeException('วันที่ผู้จัดทำ/ผู้อนุมัติไม่ถูกต้อง');
            }
        }
        $rows = $input['days'] ?? [];
        if (!is_array($rows) || count($rows) > 31) throw new RuntimeException('ตารางรายวันไม่ถูกต้อง');
        $count = self::days($data['month']);
        foreach ($rows as $day => $row) {
            if (!preg_match('/^(?:[1-9]|[12]\d|3[01])$/D', (string)$day) || (int)$day > $count || !is_array($row)) throw new RuntimeException('วันที่ในตารางไม่ตรงกับเดือนที่เลือก');
        }
        for ($day = 1; $day <= $count; $day++) {
            $row = $rows[$day] ?? [];
            $entry = [];
            foreach (self::ROW_FIELDS as $key => $label) $entry[$key] = self::text($row, $key, $key === 'notes' ? 500 : 120);
            foreach (['start', 'end'] as $key) {
                if ($entry[$key] !== '' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/D', $entry[$key])) throw new RuntimeException('เวลา ' . $key . ' วันที่ ' . $day . ' ต้องเป็น HH:MM หรือ HH:MM:SS');
            }
            // Start/end may cross midnight; do not infer elapsed time or mark success.
            $data['days'][$day] = $entry;
        }
        return $data;
    }

    private static function text(array $input, string $key, int $max, bool $required = false): string
    {
        if (isset($input[$key]) && !is_string($input[$key])) throw new RuntimeException('ข้อมูลช่อง ' . $key . ' ไม่ถูกต้อง');
        $value = Portal::text($input, $key, $max, $required);
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value)) throw new RuntimeException('ข้อมูลมีอักขระที่ไม่รองรับ');
        return $value;
    }

    public function detail(int $id, array $user): array
    {
        self::authorize($user);
        $row = $this->find($id);
        if (!$row) throw new RuntimeException('ไม่พบแบบฟอร์ม');
        $row['data'] = json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
        return $row;
    }

    public function listing(array $user, string $month, int $page): array
    {
        self::authorize($user);
        $params = $month === '' ? [] : [self::month($month)];
        $where = $params ? ' WHERE month=?' : '';
        $total = (int)$this->query('SELECT COUNT(*) FROM backup_checklists' . $where, $params)->fetchColumn();
        $paging = Portal::page($total, $page);
        $rows = $this->query('SELECT id,month,location,server_name,version,updated_at FROM backup_checklists' . $where . ' ORDER BY month DESC,id DESC LIMIT ' . $paging['size'] . ' OFFSET ' . $paging['offset'], $params)->fetchAll();
        return ['forms' => $rows, 'paging' => $paging];
    }

    public function save(?int $id, array $user, array $input): int
    {
        self::authorize($user);
        $data = self::clean($input);
        $this->db->beginTransaction();
        try {
            $version = 1;
            if ($id !== null) {
                $existing = $this->query('SELECT * FROM backup_checklists WHERE id=? FOR UPDATE', [$id])->fetch();
                if (!$existing) throw new RuntimeException('ไม่พบแบบฟอร์ม');
                if (!is_string($input['version'] ?? null) || (string)$existing['version'] !== $input['version']) throw new RuntimeException('มีผู้บันทึกฉบับใหม่แล้ว กรุณาเปิดแบบฟอร์มล่าสุดก่อนแก้ไขอีกครั้ง');
                if ($existing['month'] !== $data['month']) throw new RuntimeException('เปลี่ยนเดือนของเอกสารที่บันทึกแล้วไม่ได้ กรุณาสร้างแบบฟอร์มเดือนใหม่');
                $version = (int)$existing['version'] + 1;
            }
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $values = ['month' => $data['month'], 'location' => $data['location'], 'server_name' => $data['server_name'], 'payload' => $json, 'version' => $version, 'updated_by' => (int)$user['svp_user_id'], 'updated_at' => Portal::now()];
            if ($id === null) $id = $this->insert($values);
            else $this->update($id, $values);
            $this->query('INSERT INTO backup_checklist_history(form_id,payload,version,actor_id,created_at) VALUES(?,?,?,?,?)', [$id, $json, $version, $user['svp_user_id'], Portal::now()]);
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($e instanceof PDOException && (($e->errorInfo[1] ?? null) === 1062 || str_contains($e->getMessage(), 'UNIQUE constraint failed: backup_checklists.'))) throw new RuntimeException('มีแบบฟอร์มเดือน สถานที่ และ Server นี้แล้ว กรุณาเปิดจากรายการเดิม');
            throw $e;
        }
    }
}
