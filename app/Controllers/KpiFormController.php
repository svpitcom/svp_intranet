<?php
class KpiFormController extends Controller
{
    private function allowed(): bool
    {
        if (KpiPolicy::allows($this->currentUser())) return true;
        http_response_code(403);
        require BASE_PATH . '/app/Views/errors/403.php';
        return false;
    }
    public function index(): void
    {
        if (!$this->allowed()) return;
        try {
            $departmentId = KpiPolicy::department($this->currentUser(), $this->input('department_id', ''));
            $forms = (new KpiForm())->listing($this->currentUser(), $departmentId);
        } catch (RuntimeException $e) {
            if ($e instanceof PDOException) throw $e;
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            return;
        }
        $this->view('kpi/forms', compact('forms', 'departmentId') + ['departments' => (new Kpi())->departments($this->currentUser()), 'user' => $this->currentUser()]);
    }
    public function edit(string $id = ''): void
    {
        if (!$this->allowed()) return;
        try {
            $row = $id !== '' ? (new KpiForm())->detail((int)$id, $this->currentUser()) : null;
        } catch (RuntimeException $e) {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            return;
        }
        $editable = KpiPolicy::manages($this->currentUser(), (int)($row['department_id'] ?? $this->currentUser()['svp_department_id'] ?? 0));
        if (!$row && !$editable) {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            return;
        }
        $data = $row['data'] ?? ['year' => (int)date('Y')];
        try {
            $data['department_id'] = $row['department_id'] ?? KpiPolicy::department($this->currentUser(), $this->input('department_id', ''));
        } catch (RuntimeException $e) {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            return;
        }
        $data['version'] = $row['version'] ?? 0;
        $this->renderForm($row, $data, $editable);
    }
    private function renderForm(?array $row, array $data, bool $editable, ?string $error = null): void
    {
        $canSend = ($this->currentUser()['user_role'] ?? '') === 'admin';
        $destinations = $canSend ? $this->sharePointClient()->exportDestinations() : [];
        $deliveries = $row ? (new KpiFormDelivery())->recent((int)$row['id']) : [];
        $this->view('kpi/annual_form', compact('row', 'data', 'editable', 'error', 'canSend', 'destinations', 'deliveries') + ['departments' => (new Kpi())->departments($this->currentUser())]);
    }

    protected function sharePointClient(): SharePointClient
    {
        return new SharePointClient();
    }

    public function sendSharePoint(string $id): void
    {
        if (!$this->allowed()) return;
        if (($this->currentUser()['user_role'] ?? '') !== 'admin') {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            return;
        }
        try {
            $row = (new KpiForm())->detail((int)$id, $this->currentUser());
        } catch (RuntimeException $e) {
            if ($e instanceof PDOException) throw $e;
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return;
        }
        $format = $this->input('format', '');
        $destination = $this->input('destination', '');
        if (!in_array($format, ['xlsx', 'pdf'], true) || (string)$row['version'] !== $this->input('version', '')) {
            Session::flash('error', 'กรุณาเปิดแบบฟอร์มใหม่ เลือกรูปแบบไฟล์ และส่งฉบับที่บันทึกล่าสุด');
            $this->redirect('/kpi/forms/' . $row['id']);
            return;
        }
        try {
            $client = $this->sharePointClient();
            if (!isset($client->exportDestinations()[$destination])) throw new RuntimeException('ปลายทางไม่ถูกต้อง');
            $bytes = $format === 'xlsx' ? KpiFormExport::xlsx($row['data']) : KpiFormExport::pdf($row['data']);
            $file = $client->uploadKpiForm((int)$row['id'], (int)$row['year'], (int)$row['version'], $format, $bytes, $destination);
        } catch (Throwable $e) {
            error_log('KPI SharePoint send failed for form ' . (int)$row['id'] . ' (' . get_class($e) . ')');
            Session::flash('error', 'ข้อมูล KPI ยังอยู่ในระบบ แต่ยังยืนยันการส่งไป SharePoint ไม่ได้ กรุณาตรวจปลายทาง/การเชื่อมต่อแล้วส่งซ้ำ ไม่ต้องบันทึกแบบฟอร์มใหม่');
            $this->redirect('/kpi/forms/' . $row['id']);
            return;
        }
        try {
            (new KpiFormDelivery())->record($row, $format, $destination, $file, (int)$this->currentUser()['svp_user_id']);
            Session::flash('success', 'ส่ง ' . strtoupper($format) . ' ฉบับที่ ' . $row['version'] . ' ไป SharePoint แล้ว');
        } catch (Throwable $e) {
            error_log('KPI SharePoint receipt save failed for form ' . (int)$row['id']);
            Session::flash('error', 'SharePoint รับไฟล์ ' . $file['name'] . ' แล้ว แต่บันทึกประวัติการส่งใน Intranet ไม่สำเร็จ กรุณาตรวจไฟล์ที่ปลายทาง');
        }
        $this->redirect('/kpi/forms/' . $row['id']);
    }
    public function save(string $id = ''): void
    {
        if (!$this->allowed()) return;
        $model = new KpiForm();
        $row = null;
        // Check existing document access before validation or redisplaying any content.
        if ($id !== '') {
            try {
                $row = $model->detail((int)$id, $this->currentUser());
            } catch (RuntimeException $e) {
                http_response_code(403);
                require BASE_PATH . '/app/Views/errors/403.php';
                return;
            }
        }
        if (!KpiPolicy::manages($this->currentUser(), (int)($row['department_id'] ?? $this->currentUser()['svp_department_id'] ?? 0))) {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            return;
        }
        try {
            $saved = $model->save($id !== '' ? (int)$id : null, $this->currentUser(), $_POST);
        } catch (RuntimeException $e) {
            if ($e instanceof PDOException) throw $e;
            http_response_code(422);
            $this->renderForm($row, $_POST, true, $e->getMessage());
            return;
        }
        Session::flash('success', 'บันทึก KPI & Action plan แล้ว');
        $this->redirect('/kpi/forms/' . $saved);
    }
    public function export(string $id): void
    {
        if (!$this->allowed()) return;
        try {
            $row = (new KpiForm())->detail((int)$id, $this->currentUser());
        } catch (RuntimeException $e) {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            return;
        }
        $format = $this->input('format', 'xlsx');
        if (!in_array($format, ['xlsx', 'pdf'], true)) {
            http_response_code(400);
            echo 'Unsupported format';
            return;
        }
        $bytes = $format === 'xlsx' ? KpiFormExport::xlsx($row['data']) : KpiFormExport::pdf($row['data']);
        header('Cache-Control: private, no-store');
        header('Content-Type: ' . ($format === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'application/pdf'));
        header('Content-Disposition: attachment; filename="MR-F-027-01-' . $row['year'] . '-' . $row['id'] . '.' . $format . '"');
        header('X-Content-Type-Options: nosniff');
        echo $bytes;
    }
}
