<?php
class Kpi extends Model
{
    protected string $table = 'kpi_definitions';
    public const SOURCES = ['manual' => 'กรอกผลเอง', 'pm_completed' => 'จำนวนบันทึก PM สำเร็จ', 'pm_success_rate' => 'สัดส่วนบันทึก PM สำเร็จ (%)', 'tickets_resolved' => 'จำนวนงานซ่อมที่แก้ไขครั้งแรก', 'repair_hours' => 'ชั่วโมงเฉลี่ยจนแก้ไขครั้งแรก'];

    public function people(array $user): array
    {
        $admin = ($user['user_role'] ?? '') === 'admin';
        return $this->query('SELECT svp_user_id,first_name,last_name,svp_department_id FROM users WHERE is_active=1' . ($admin ? '' : ' AND svp_department_id=:dept') . ' ORDER BY first_name', $admin ? [] : ['dept' => (int)($user['svp_department_id'] ?? 0)])->fetchAll();
    }
    public function departments(array $user): array
    {
        $admin = ($user['user_role'] ?? '') === 'admin';
        return $this->query('SELECT svp_department_id,svp_department_name FROM department' . ($admin ? '' : ' WHERE svp_department_id=:dept') . ' ORDER BY svp_department_name', $admin ? [] : ['dept' => (int)($user['svp_department_id'] ?? 0)])->fetchAll();
    }
    public function createDefinition(array $user, array $data): int
    {
        $dept = Portal::id($data['department_id'] ?? '') ?? 0;
        if (!KpiPolicy::manages($user, $dept)) throw new RuntimeException('ไม่มีสิทธิ์ตั้ง KPI ของแผนกนี้');
        $period = KpiPolicy::period($data['period'] ?? '');
        $person = Portal::id($data['user_id'] ?? '');
        if ($person !== null) throw new RuntimeException('KPI ใหม่ต้องเป็นระดับแผนก ไม่ระบุพนักงานรายบุคคล');
        if (!$this->query('SELECT svp_department_id FROM department WHERE svp_department_id=?', [$dept])->fetch()) throw new RuntimeException('ไม่พบแผนก');
        if ($person && !$this->query('SELECT svp_user_id FROM users WHERE svp_user_id=? AND svp_department_id=? AND is_active=1', [$person, $dept])->fetch()) throw new RuntimeException('พนักงานต้องอยู่ในแผนกที่เลือกและมีสถานะใช้งาน');
        $source = $data['source'] ?? '';
        $direction = $data['direction'] ?? '';
        if (!isset(self::SOURCES[$source]) || !in_array($direction, ['higher', 'lower'], true)) throw new RuntimeException('ประเภท KPI หรือวิธีคิดคะแนนไม่ถูกต้อง');
        $target = KpiPolicy::number($data['target'] ?? '', true);
        $weight = KpiPolicy::number($data['weight'] ?? '', true);
        if ($weight > 100) throw new RuntimeException('น้ำหนักต้องไม่เกิน 100');
        if ($source !== 'manual') {
            $direction = $source === 'repair_hours' ? 'lower' : 'higher';
            if ($source === 'pm_success_rate' && $target > 100) throw new RuntimeException('เป้าหมายร้อยละต้องไม่เกิน 100');
        }
        return $this->insert([
            'period' => $period,
            'title' => Portal::text($data, 'title', 200),
            'department_id' => $dept,
            'user_id' => $person,
            'source' => $source,
            'direction' => $direction,
            'target' => $target,
            'weight' => $weight,
            'unit' => $source === 'manual' ? Portal::text($data, 'unit', 40) : ($source === 'repair_hours' ? 'ชั่วโมง' : ($source === 'pm_success_rate' ? '%' : 'รายการ')),
            'created_by' => (int)$user['svp_user_id'],
            'created_at' => Portal::now()
        ]);
    }
    public function detail(int $id, array $user): array
    {
        $row = $this->find($id);
        if (!$row || !KpiPolicy::reads($user, $row)) throw new RuntimeException('ไม่พบ KPI หรือไม่มีสิทธิ์เข้าถึง');
        return $row;
    }
    public function saveResult(int $id, array $user, array $data): void
    {
        $row = $this->detail($id, $user);
        if (!KpiPolicy::writes($user, $row)) throw new RuntimeException('ไม่มีสิทธิ์บันทึก KPI นี้ หรือเป็น KPI อัตโนมัติ');
        if ($row['period'] > substr(Portal::today(), 0, 7)) throw new RuntimeException('ยังบันทึกผลของเดือนอนาคตไม่ได้');
        $actual = KpiPolicy::number($data['actual'] ?? '');
        $note = Portal::text($data, 'note', 4000, false);
        $version = filter_var($data['version'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($version === false || $version === null) throw new RuntimeException('กรุณาโหลดหน้าใหม่');
        $this->db->beginTransaction();
        try {
            // Lock the immutable definition to serialize first insert and subsequent writes.
            $this->query('SELECT id FROM kpi_definitions WHERE id=? FOR UPDATE', [$id]);
            $previous = $this->query('SELECT version FROM kpi_results WHERE kpi_id=?', [$id])->fetch();
            if ((int)($previous['version'] ?? 0) !== $version) throw new RuntimeException('ข้อมูลถูกแก้ไขแล้ว กรุณาโหลดหน้าใหม่ก่อนบันทึก');
            $next = $version + 1;
            $actor = (int)$user['svp_user_id'];
            $now = Portal::now();
            if ($previous) $this->query('UPDATE kpi_results SET actual=?,note=?,version=?,updated_by=?,updated_at=? WHERE kpi_id=?', [$actual, $note, $next, $actor, $now, $id]);
            else $this->query('INSERT INTO kpi_results(kpi_id,actual,note,version,updated_by,updated_at) VALUES(?,?,?,?,?,?)', [$id, $actual, $note, $next, $actor, $now]);
            $this->query('INSERT INTO kpi_history(kpi_id,actual,note,version,actor_id,created_at) VALUES(?,?,?,?,?,?)', [$id, $actual, $note, $next, $actor, $now]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
    public function history(int $id, array $user): array
    {
        $this->detail($id, $user);
        return $this->query('SELECT h.*,u.first_name,u.last_name FROM kpi_history h LEFT JOIN users u ON u.svp_user_id=h.actor_id WHERE h.kpi_id=? ORDER BY h.version DESC LIMIT 100', [$id])->fetchAll();
    }
    public function listing(array $user, string $period, $requestedDepartment = null): array
    {
        KpiPolicy::period($period);
        if (!KpiPolicy::allows($user)) throw new RuntimeException('ไม่มีสิทธิ์เข้า KPI');
        $departmentId = KpiPolicy::department($user, $requestedDepartment);
        $departments = $this->departments($user);
        $where = 'k.period=? AND k.user_id IS NULL';
        $params = [$period];
        if ($departmentId !== null) {
            $where .= ' AND k.department_id=?';
            $params[] = $departmentId;
        }
        $rows = $this->query('SELECT k.*,d.svp_department_name,u.first_name,u.last_name,r.actual,r.note,r.version,r.updated_at FROM kpi_definitions k LEFT JOIN department d ON d.svp_department_id=k.department_id LEFT JOIN users u ON u.svp_user_id=k.user_id LEFT JOIN kpi_results r ON r.kpi_id=k.id WHERE ' . $where . ' ORDER BY k.department_id,k.user_id,k.id', $params)->fetchAll();
        $auto = [];
        $summaries = [];
        foreach ($departments as $department) {
            $id = (int)$department['svp_department_id'];
            if ($departmentId !== null && $id !== $departmentId) continue;
            $summaries[$id . ':0'] = ['department_id' => $id, 'label' => $department['svp_department_name'], 'total' => 0, 'filled' => 0, 'weighted' => 0, 'weight' => 0];
        }
        foreach ($rows as &$row) {
            if ($row['source'] !== 'manual') {
                $scope = $row['department_id'] . ':' . ($row['user_id'] ?? 0);
                if (!isset($auto[$scope])) $auto[$scope] = $this->automatic($period, (int)$row['department_id'], $row['user_id'] ? (int)$row['user_id'] : null);
                $row['actual'] = $auto[$scope][$row['source']];
            }
            $row['score'] = KpiPolicy::score($row['actual'] === null ? null : (float)$row['actual'], (float)$row['target'], $row['direction']);
            $scope = $row['department_id'] . ':' . ($row['user_id'] ?? 0);
            if (!isset($summaries[$scope])) $summaries[$scope] = ['department_id' => (int)$row['department_id'], 'label' => $row['svp_department_name'], 'total' => 0, 'filled' => 0, 'weighted' => 0, 'weight' => 0];
            $s = &$summaries[$scope];
            $s['total']++;
            if ($row['score'] !== null) {
                $s['filled']++;
                $s['weighted'] += $row['score'] * (float)$row['weight'];
                $s['weight'] += (float)$row['weight'];
            }
            unset($s);
        }
        unset($row);
        return compact('rows', 'summaries', 'departments', 'departmentId');
    }
    protected function automatic(string $period, int $dept, ?int $person): array
    {
        $start = $period . '-01';
        $end = (new DateTimeImmutable($start))->modify('+1 month')->format('Y-m-d');
        if ($start > Portal::today()) return array_fill_keys(['pm_completed', 'pm_success_rate', 'tickets_resolved', 'repair_hours'], null);
        // Attribution uses PM performer / first resolution actor and their CURRENT department.
        $scope = $person ? 'u.svp_user_id=? AND u.svp_department_id=?' : 'u.svp_department_id=?';
        $ids = $person ? [$person, $dept] : [$dept];
        $pm = $this->query("SELECT COUNT(*) AS total,SUM(CASE WHEN r.result_status='completed' THEN 1 ELSE 0 END) AS completed FROM pm_record r JOIN users u ON u.svp_user_id=r.performed_by WHERE r.performed_date>=? AND r.performed_date<? AND $scope", array_merge([$start, $end], $ids))->fetch();
        $work = $this->query("SELECT COUNT(*) AS total,AVG(TIMESTAMPDIFF(SECOND,w.created_at,e.created_at)/3600.0) AS hours FROM work_items w JOIN (SELECT work_id,MIN(id) AS event_id FROM work_events WHERE action='resolve' GROUP BY work_id) first_event ON first_event.work_id=w.id JOIN work_events e ON e.id=first_event.event_id JOIN users u ON u.svp_user_id=e.actor_id WHERE w.kind='ticket' AND e.created_at>=? AND e.created_at<? AND e.created_at>=w.created_at AND $scope", array_merge([$start, $end], $ids))->fetch();
        return ['pm_completed' => (float)($pm['completed'] ?? 0), 'pm_success_rate' => (int)$pm['total'] > 0 ? (float)$pm['completed'] / (int)$pm['total'] * 100 : null, 'tickets_resolved' => (float)$work['total'], 'repair_hours' => $work['hours'] === null ? null : (float)$work['hours']];
    }
}
