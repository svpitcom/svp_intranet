<?php
class WorkItem extends Model
{
    protected string $table = 'work_items';
    public const STATUSES = ['open' => 'แจ้งใหม่', 'in_progress' => 'กำลังดำเนินการ', 'resolved' => 'ซ่อมเสร็จ รอยืนยัน', 'closed' => 'ปิดงาน', 'pending' => 'รออนุมัติ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ไม่อนุมัติ', 'cancelled' => 'ยกเลิก'];
    public const PRIORITIES = ['low' => 'ต่ำ', 'normal' => 'ปกติ', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'];
    public const CATEGORIES = ['ticket' => ['it' => 'คอมพิวเตอร์ / IT', 'machine' => 'เครื่องจักร', 'facility' => 'อาคาร / สาธารณูปโภค', 'other' => 'อื่น ๆ'], 'request' => ['purchase' => 'ขอจัดซื้อ', 'access' => 'ขอสิทธิ์ใช้งาน', 'equipment' => 'ขอใช้อุปกรณ์', 'general' => 'คำขอทั่วไป']];

    public static function canRead(array $row, array $user): bool
    {
        if (empty($user['is_active']) || (DccWorkspace::restricted($user) && $row['kind'] !== 'ticket')) return false;
        $id = (int)$user['svp_user_id'];
        return ($user['user_role'] ?? '') === 'admin' || (int)$row['requester_id'] === $id
            || ($row['kind'] === 'ticket' ? (self::managesTickets($user)
                || (self::receivesMachineTickets($user) && ($row['category'] ?? '') === 'machine')
                || (int)($row['assignee_id'] ?? 0) === $id) : (int)($row['approver_id'] ?? 0) === $id);
    }
    public static function receivesMachineTickets(array $user): bool
    {
        return !empty($user['is_active']) && in_array($user['svp_department_id'] ?? null, [6, '6'], true);
    }
    public static function managesTickets(array $user): bool
    {
        return !empty($user['is_active']) && Portal::manager($user) && !DccWorkspace::restricted($user);
    }
    public static function actions(array $row, array $user): array
    {
        if (!self::canRead($row, $user)) return [];
        $id = (int)$user['svp_user_id'];
        $owner = (int)$row['requester_id'] === $id;
        $status = $row['status'];
        $actions = [];
        if ($row['kind'] === 'request') {
            if ($status === 'pending' && (int)$row['approver_id'] === $id && !$owner && Portal::manager($user)) $actions = ['approve', 'reject'];
            if ($status === 'pending' && $owner) $actions[] = 'cancel';
        } else {
            $operator = self::managesTickets($user) || (int)($row['assignee_id'] ?? 0) === $id;
            if (in_array($status, ['open', 'in_progress'], true)) {
                if (self::managesTickets($user)) $actions[] = 'assign';
                if ($status === 'open' && empty($row['assignee_id']) && $row['category'] === 'machine' && self::receivesMachineTickets($user)) $actions[] = 'claim';
                if ($operator && $status === 'open') $actions[] = 'start';
                if ($operator && $status === 'in_progress') $actions[] = 'resolve';
                if ($operator && $status === 'in_progress' && $row['category'] === 'machine') $actions[] = 'save_repair';
                if ($owner || self::managesTickets($user)) $actions[] = 'cancel';
            }
            if ($status === 'resolved' && ($owner || self::managesTickets($user))) $actions = ['close', 'reopen'];
        }
        return $actions;
    }
    private function scope(array $user, string $kind): array
    {
        $where = 'w.kind=:kind';
        $params = ['kind' => $kind];
        if (empty($user['is_active']) || (DccWorkspace::restricted($user) && $kind !== 'ticket')) $where .= ' AND 1=0';
        if (($user['user_role'] ?? '') !== 'admin' && !($kind === 'ticket' && self::managesTickets($user))) {
            $column = $kind === 'ticket' ? 'assignee_id' : 'approver_id';
            $machineQueue = $kind === 'ticket' && self::receivesMachineTickets($user);
            $where .= " AND (w.requester_id=:owner OR w.{$column}=:participant" . ($machineQueue ? " OR w.category=:repair_category" : '') . ')';
            $params += ['owner' => (int)$user['svp_user_id'], 'participant' => (int)$user['svp_user_id']];
            if ($machineQueue) $params['repair_category'] = 'machine';
        }
        return [$where, $params];
    }
    public function listing(array $user, string $kind, string $q = '', string $status = '', int $page = 1, bool $approvalOnly = false): array
    {
        [$where, $params] = $this->scope($user, $kind);
        if ($kind === 'request' && $approvalOnly) {
            $where .= ' AND w.approver_id=:my_approval';
            $params['my_approval'] = (int)$user['svp_user_id'];
        }
        if ($q !== '') {
            $where .= " AND (w.title LIKE :q OR w.description LIKE :q2)";
            $params += ['q' => '%' . $q . '%', 'q2' => '%' . $q . '%'];
        }
        if (isset(self::STATUSES[$status])) {
            $where .= ' AND w.status=:status';
            $params['status'] = $status;
        }
        $paging = Portal::page((int)$this->query('SELECT COUNT(*) FROM work_items w WHERE ' . $where, $params)->fetchColumn(), $page);
        $rows = $this->query('SELECT w.*, u.first_name, u.last_name FROM work_items w LEFT JOIN users u ON u.svp_user_id=w.requester_id WHERE ' . $where . ' ORDER BY w.updated_at DESC,w.id DESC LIMIT 15 OFFSET ' . $paging['offset'], $params)->fetchAll();
        return $paging + ['rows' => $rows];
    }
    public function detail(int $id, array $user): array
    {
        $row = $this->query('SELECT w.*, u.first_name, u.last_name, a.first_name AS assignee_name, a.last_name AS assignee_last, p.first_name AS approver_name, p.last_name AS approver_last, d.svp_device_name FROM work_items w LEFT JOIN users u ON u.svp_user_id=w.requester_id LEFT JOIN users a ON a.svp_user_id=w.assignee_id LEFT JOIN users p ON p.svp_user_id=w.approver_id LEFT JOIN device d ON d.svp_device_id=w.device_id WHERE w.id=:id', ['id' => $id])->fetch();
        if (!$row || !self::canRead($row, $user)) throw new RuntimeException('ไม่พบรายการหรือคุณไม่มีสิทธิ์เข้าถึง');
        $row['machine_form'] = $row['kind'] === 'ticket' && $row['category'] === 'machine' ? (new MachineRepairForm())->load($id) : null;
        return $row;
    }
    public function people(bool $approvers = false): array
    {
        return $this->query("SELECT svp_user_id,first_name,last_name,user_role,is_active,svp_department_id FROM users WHERE is_active=1" . ($approvers ? " AND user_role IN ('admin','manager')" : '') . ' ORDER BY first_name')->fetchAll();
    }
    public static function canAssignTo(array $item, array $person): bool
    {
        return !empty($person['is_active']) && ($item['category'] !== 'machine' || self::receivesMachineTickets($person));
    }
    public function assigneesFor(array $item): array
    {
        return array_values(array_filter($this->people(), static fn($person) => self::canAssignTo($item, $person)));
    }
    private function activeUser(int $id, bool $approver = false): void
    {
        $sql = "SELECT svp_user_id FROM users WHERE svp_user_id=:id AND is_active=1" . ($approver ? " AND user_role IN ('admin','manager')" : '');
        if (!$this->query($sql, ['id' => $id])->fetch()) throw new RuntimeException('ผู้รับผิดชอบหรือผู้อนุมัติไม่พร้อมใช้งาน');
    }
    private function event(int $id, int $actor, string $action, string $body): void
    {
        $this->query('INSERT INTO work_events(work_id,actor_id,action,body,created_at) VALUES (:id,:actor,:action,:body,:now)', compact('id', 'actor', 'action', 'body') + ['now' => Portal::now()]);
    }
    private function notify(int $id, array $recipients, int $actor, string $message): void
    {
        foreach (array_unique(array_map('intval', $recipients)) as $recipient) {
            if ($recipient < 1 || $recipient === $actor) continue;
            $this->query('INSERT INTO portal_notifications(user_id,work_id,message,created_at) VALUES (:user,:id,:message,:now)', ['user' => $recipient, 'id' => $id, 'message' => mb_substr($message, 0, 255), 'now' => Portal::now()]);
        }
    }
    public function createItem(string $kind, array $data, array $user, ?array $file = null): int
    {
        if (empty($user['is_active']) || empty($user['svp_user_id']) || (DccWorkspace::restricted($user) && $kind !== 'ticket')) throw new RuntimeException('ไม่มีสิทธิ์สร้างรายการนี้');
        if (!isset(self::CATEGORIES[$kind])) throw new RuntimeException('ประเภทงานไม่ถูกต้อง');
        $title = Portal::text($data, 'title', 200);
        $description = Portal::text($data, 'description', 10000);
        $category = $data['category'] ?? '';
        $priority = $kind === 'ticket' && $category === 'machine' ? 'normal' : ($data['priority'] ?? 'normal');
        if (!is_string($category) || !isset(self::CATEGORIES[$kind][$category]) || !is_string($priority) || !isset(self::PRIORITIES[$priority])) throw new RuntimeException('ประเภทหรือความเร่งด่วนไม่ถูกต้อง');
        $device = Portal::id($data['device_id'] ?? null);
        $machineForm = null;
        if ($kind === 'ticket' && $category === 'machine') {
            $machineForm = MachineRepairForm::requestData($data, $user);
            $priority = MachineRepairForm::PRIORITY_MAP[$machineForm['request_priority']];
            $device = null;
        }
        $approver = Portal::id($data['approver_id'] ?? null);
        if ($device && !$this->query('SELECT svp_device_id FROM device WHERE svp_device_id=:id AND is_active=1', ['id' => $device])->fetch()) throw new RuntimeException('ไม่พบอุปกรณ์ที่ใช้งานอยู่');
        if ($kind === 'request') {
            if (!$approver || $approver === (int)$user['svp_user_id']) throw new RuntimeException('เลือกผู้อนุมัติคนอื่นที่มีสิทธิ์ผู้จัดการหรือผู้ดูแล');
            $this->activeUser($approver, true);
        }
        $due = Portal::text($data, 'due_date', 10, false);
        if ($due !== '' && (!Validation::date($due) || $due < Portal::today())) throw new RuntimeException('วันที่ต้องการต้องเป็นวันนี้หรือวันถัดไป');
        $payload = ['kind' => $kind, 'title' => $title, 'description' => $description, 'category' => $category, 'priority' => $priority, 'device_id' => $device, 'location' => Portal::text($data, 'location', 200, false), 'requester_id' => (int)$user['svp_user_id'], 'approver_id' => $kind === 'request' ? $approver : null, 'status' => $kind === 'ticket' ? 'open' : 'pending', 'due_date' => ($machineForm !== null ? $machineForm['access_date'] : $due) ?: null, 'attachment_path' => $file['stored_path'] ?? null, 'attachment_name' => $file['original_name'] ?? null, 'created_at' => Portal::now(), 'updated_at' => Portal::now()];
        $this->db->beginTransaction();
        try {
            $id = $this->insert($payload);
            if ($machineForm !== null) (new MachineRepairForm())->createFor($id, $machineForm);
            $this->event($id, (int)$user['svp_user_id'], 'create', 'สร้างรายการ');
            $recipients = $kind === 'request' ? [$approver] : array_column(array_filter($this->people(true), [self::class, 'managesTickets']), 'svp_user_id');
            $this->notify($id, $recipients, (int)$user['svp_user_id'], 'รายการใหม่ #' . $id . ' ' . $title);
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
    public function history(int $id): array
    {
        return $this->query('SELECT e.*,u.first_name,u.last_name FROM work_events e LEFT JOIN users u ON u.svp_user_id=e.actor_id WHERE e.work_id=:id ORDER BY e.id', ['id' => $id])->fetchAll();
    }
    public function act(int $id, array $user, string $action, int $version, string $note, ?int $assignee, array $formInput = []): void
    {
        if (mb_strlen($note) > 10000) throw new RuntimeException('ข้อความยาวเกิน 10,000 ตัวอักษร');
        $this->db->beginTransaction();
        try {
            $row = $this->query('SELECT * FROM work_items WHERE id=:id FOR UPDATE', ['id' => $id])->fetch();
            if (!$row || !self::canRead($row, $user)) throw new RuntimeException('ไม่พบรายการหรือไม่มีสิทธิ์');
            if ((int)$row['version'] !== $version) throw new RuntimeException('รายการถูกแก้ไขแล้ว กรุณาโหลดหน้าใหม่ก่อนทำรายการ');
            if ($action !== 'comment' && !in_array($action, self::actions($row, $user), true)) throw new RuntimeException('ไม่มีสิทธิ์ทำรายการนี้ในสถานะปัจจุบัน');
            $form = $row['kind'] === 'ticket' && $row['category'] === 'machine' ? (new MachineRepairForm())->load($id) : null;
            $section = null;
            $sectionData = [];
            if ($form && in_array($action, ['save_repair', 'resolve'], true)) {
                $section = 'repair_data';
                $sectionData = MachineRepairForm::repairData(array_replace($form['repair_data'], $formInput), $action === 'resolve', $user);
                if ($sectionData['received_date'] !== '' && $sectionData['received_date'] < $form['request_data']['report_date']) throw new RuntimeException('วันที่รับแจ้งต้องไม่ก่อนวันที่แจ้งซ่อม');
                if ($action === 'resolve') $note = $sectionData['repair_detail'];
                else $note = 'บันทึกส่วนผู้รับแจ้งซ่อมและรายการอะไหล่';
            } elseif ($action === 'save_repair') throw new RuntimeException('ใบงานเดิมไม่มีแบบฟอร์ม MN-F-001-02');
            if ($form && in_array($action, ['close', 'reopen'], true)) {
                $section = 'acceptance_data';
                $sectionData = MachineRepairForm::acceptanceData($formInput, $action, $user);
                if ($sectionData['inspection_date'] < ($form['repair_data']['repair_end_date'] ?? $form['request_data']['report_date'])) throw new RuntimeException('วันที่ตรวจรับต้องไม่ก่อนวันที่ซ่อมเสร็จ');
                $note = $sectionData['suggestions'] ?: 'ตรวจรับ: พร้อมใช้งานปกติ';
            }
            if (in_array($action, ['comment', 'resolve', 'reject', 'cancel', 'reopen'], true) && trim($note) === '') throw new RuntimeException('กรุณาระบุรายละเอียดหรือเหตุผล');
            $next = ['claim' => 'in_progress', 'start' => 'in_progress', 'resolve' => 'resolved', 'close' => 'closed', 'reopen' => 'in_progress', 'cancel' => 'cancelled', 'approve' => 'approved', 'reject' => 'rejected'][$action] ?? $row['status'];
            $data = ['status' => $next, 'updated_at' => Portal::now(), 'version' => $version + 1];
            if ($action === 'claim') $data['assignee_id'] = (int)$user['svp_user_id'];
            if ($action === 'assign') {
                if (!$assignee) throw new RuntimeException('กรุณาเลือกผู้รับงาน');
                $person = (new User())->findById($assignee);
                if (!$person || !self::canAssignTo($row, $person)) throw new RuntimeException($row['category'] === 'machine' ? 'กรุณาเลือกผู้รับงานจากแผนก Maintenance ที่ยังใช้งานอยู่' : 'ผู้รับงานไม่พร้อมใช้งาน');
                $data['assignee_id'] = $assignee;
                $note = 'มอบหมายผู้รับงาน #' . $assignee . ($note !== '' ? ' — ' . $note : '');
            }
            if ($action === 'resolve') $data['resolution'] = $note;
            $this->update($id, $data);
            if ($section !== null) (new MachineRepairForm())->saveSection($id, $section, $sectionData);
            $actor = (int)$user['svp_user_id'];
            $this->event($id, $actor, $action, $note !== '' ? $note : (self::STATUSES[$next] ?? $next));
            if ($section !== null) $this->event($id, $actor, 'form_snapshot', json_encode([$section => $sectionData], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $message = match ($action) {
                'resolve' => 'ซ่อมเสร็จแล้ว กรุณาตรวจสอบและยืนยันปิดเคส หรือส่งกลับแก้ไข',
                'close' => 'ปิดเคสแล้ว',
                'reopen' => 'ส่งกลับแก้ไข กรุณาตรวจสอบรายละเอียดจากผู้แจ้ง',
                default => self::STATUSES[$next],
            };
            $this->notify($id, [$row['requester_id'], $data['assignee_id'] ?? $row['assignee_id'], $row['approver_id']], $actor, '#' . $id . ' ' . $message . ' · ' . $row['title']);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
    public function overview(array $user): array
    {
        $counts = [];
        foreach (['ticket', 'request'] as $kind) {
            [$where, $params] = $this->scope($user, $kind);
            $counts[$kind] = (int)$this->query("SELECT COUNT(*) FROM work_items w WHERE {$where} AND w.status IN ('open','in_progress','resolved','pending')", $params)->fetchColumn();
        }
        $counts['approval'] = (int)$this->query("SELECT COUNT(*) FROM work_items WHERE kind='request' AND status='pending' AND approver_id=:id", ['id' => $user['svp_user_id']])->fetchColumn();
        $counts['unread'] = (new PortalNotification())->unread((int)$user['svp_user_id'], DccWorkspace::restricted($user));
        return $counts;
    }
}
