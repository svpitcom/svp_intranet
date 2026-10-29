<?php
class BackupChecklistController extends Controller
{
    private function allowed(): bool
    {
        if (ItDeviceAccess::allows($this->currentUser())) return true;
        http_response_code(403);
        require BASE_PATH . '/app/Views/errors/403.php';
        return false;
    }

    public function index(): void
    {
        if (!$this->allowed()) return;
        $month = $this->input('month', '');
        try {
            $result = (new BackupChecklist())->listing($this->currentUser(), $month, (int)$this->input('page', 1));
        } catch (RuntimeException $e) {
            if ($e instanceof PDOException) throw $e;
            http_response_code(422);
            echo Portal::esc($e->getMessage());
            return;
        }
        $this->view('backup_checklists/index', $result + ['month' => $month]);
    }

    private function find(string $id): ?array
    {
        try {
            return (new BackupChecklist())->detail((int)$id, $this->currentUser());
        } catch (RuntimeException $e) {
            if ($e instanceof PDOException) throw $e;
            http_response_code(404);
            require BASE_PATH . '/app/Views/errors/404.php';
            return null;
        }
    }

    public function edit(string $id = ''): void
    {
        if (!$this->allowed()) return;
        $row = $id !== '' ? $this->find($id) : null;
        if ($id !== '' && !$row) return;
        if ($row) $data = $row['data'] + ['version' => $row['version']];
        else {
            try {
                $month = BackupChecklist::month($this->input('month', substr(Portal::today(), 0, 7)));
            } catch (RuntimeException $e) {
                http_response_code(422);
                echo Portal::esc($e->getMessage());
                return;
            }
            $data = BackupChecklist::clean(['month' => $month, 'location' => 'SVP', 'server_name' => 'SVP-FILE Server on Cloud']) + ['version' => 0];
        }
        $this->view('backup_checklists/form', ['row' => $row, 'data' => $data, 'error' => null]);
    }

    public function save(string $id = ''): void
    {
        if (!$this->allowed()) return;
        $row = $id !== '' ? $this->find($id) : null;
        if ($id !== '' && !$row) return;
        try {
            $saved = (new BackupChecklist())->save($id !== '' ? (int)$id : null, $this->currentUser(), $_POST);
        } catch (RuntimeException $e) {
            if ($e instanceof PDOException) throw $e;
            http_response_code(422);
            // Preserve the submitted version so a stale page cannot silently overwrite newer work.
            $data = $_POST;
            try {
                BackupChecklist::month($data['month'] ?? '');
            } catch (RuntimeException $badMonth) {
                $data['month'] = $row['month'] ?? substr(Portal::today(), 0, 7);
            }
            $this->view('backup_checklists/form', ['row' => $row, 'data' => $data, 'error' => $e->getMessage()]);
            return;
        }
        Session::flash('success', 'บันทึกแบบตรวจ Backup Server IT-F-004 แล้ว');
        $this->redirect('/it-backup/' . $saved);
    }

    public function export(string $id): void
    {
        if (!$this->allowed()) return;
        $row = $this->find($id);
        if (!$row) return;
        $format = $this->input('format', 'xlsx');
        if (!in_array($format, ['xlsx', 'pdf'], true)) {
            http_response_code(400);
            echo 'Unsupported format';
            return;
        }
        $bytes = $format === 'xlsx' ? BackupChecklistExport::xlsx($row['data']) : BackupChecklistExport::pdf($row['data']);
        header('Cache-Control: private, no-store');
        header('Content-Type: ' . ($format === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'application/pdf'));
        header('Content-Disposition: attachment; filename="IT-F-004-' . $row['month'] . '-' . $row['id'] . '-v' . $row['version'] . '.' . $format . '"');
        header('X-Content-Type-Options: nosniff');
        echo $bytes;
    }
}
