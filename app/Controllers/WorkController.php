<?php
class WorkController extends Controller
{
    public function tickets(): void
    {
        $this->listing('ticket');
    }
    public function requests(): void
    {
        $this->listing('request');
    }
    private function listing(string $kind): void
    {
        $q = mb_substr($this->input('q', ''), 0, 200);
        $status = $this->input('status', '');
        $approvalOnly = $kind === 'request' && $this->input('approval', '') === '1';
        $data = (new WorkItem())->listing($this->currentUser(), $kind, $q, $status, (int)$this->input('page', '1'), $approvalOnly);
        $this->view('work/index', $data + compact('kind', 'q', 'status', 'approvalOnly'));
    }
    public function newTicket(): void
    {
        $this->form('ticket');
    }
    public function newRequest(): void
    {
        $this->form('request');
    }
    private function form(string $kind, array $data = [], string $error = ''): void
    {
        $model = new WorkItem();
        $this->view('work/form', compact('kind', 'data', 'error') + ['people' => $model->people(true), 'devices' => array_filter((new Device())->all(), static fn($d) => !empty($d['is_active']))]);
    }
    public function createTicket(): void
    {
        $this->create('ticket');
    }
    public function createRequest(): void
    {
        $this->create('request');
    }
    private function create(string $kind): void
    {
        $data = [];
        foreach (array_merge(['title', 'description', 'category', 'priority', 'device_id', 'location', 'due_date', 'approver_id'], MachineRepairForm::REQUEST_FIELDS) as $key) $data[$key] = $this->input($key, '');
        $file = null;
        try {
            $file = $kind === 'ticket' ? FileUploader::uploadImage('attachment', BASE_PATH . '/public/uploads/work') : FileUploader::uploadPdf('attachment', BASE_PATH . '/public/uploads/work');
            $id = (new WorkItem())->createItem($kind, $data, $this->currentUser(), $file);
        } catch (Throwable $e) {
            FileUploader::discard(BASE_PATH . '/public/uploads/work', $file['stored_path'] ?? null);
            FileUploader::discardImage(BASE_PATH . '/public/uploads/work', $file['stored_path'] ?? null);
            http_response_code(422);
            $this->form($kind, $data, $e instanceof PDOException ? 'บันทึกไม่สำเร็จ กรุณาลองใหม่' : $e->getMessage());
            return;
        }
        Session::flash('success', 'ส่งรายการแล้ว สามารถติดตามความคืบหน้าได้ที่หน้านี้');
        $this->redirect('/work/' . $id);
    }
    public function show(string $id): void
    {
        try {
            $model = new WorkItem();
            $item = $model->detail((int)$id, $this->currentUser());
        } catch (RuntimeException $e) {
            http_response_code(404);
            echo 'ไม่พบรายการหรือไม่มีสิทธิ์เข้าถึง';
            return;
        }
        $this->view('work/show', ['item' => $item, 'events' => $model->history((int)$id), 'people' => $model->assigneesFor($item), 'actions' => WorkItem::actions($item, $this->currentUser())]);
    }
    public function action(string $id): void
    {
        $formInput = [];
        foreach (array_merge(MachineRepairForm::REPAIR_FIELDS, MachineRepairForm::ACCEPTANCE_FIELDS) as $key) if (array_key_exists($key, $_POST)) $formInput[$key] = $this->input($key, '');
        foreach (['technicians', 'parts'] as $key) if (array_key_exists($key, $_POST)) $formInput[$key] = $_POST[$key];
        try {
            (new WorkItem())->act((int)$id, $this->currentUser(), $this->input('action', ''), (int)$this->input('version', '0'), $this->input('note', ''), Portal::id($this->input('assignee_id', '')), $formInput);
            Session::flash('success', 'บันทึกการเปลี่ยนแปลงและแจ้งผู้เกี่ยวข้องในระบบแล้ว');
        } catch (RuntimeException $e) {
            Session::flash('error', $e instanceof PDOException ? 'บันทึกไม่สำเร็จ กรุณาลองใหม่' : $e->getMessage());
            Session::set('work_form_input', ['id' => (int)$id, 'data' => MachineRepairForm::displayInput($formInput)]);
        }
        $this->redirect('/work/' . (int)$id);
    }
    public function attachment(string $id): void
    {
        try {
            $item = (new WorkItem())->detail((int)$id, $this->currentUser());
        } catch (RuntimeException $e) {
            http_response_code(404);
            return;
        }
        $imagePath = $item['kind'] === 'ticket' ? FileUploader::resolveImage(BASE_PATH . '/public/uploads/work', $item['attachment_path']) : null;
        $path = $imagePath ?? FileUploader::resolve(BASE_PATH . '/public/uploads/work', $item['attachment_path']);
        if (!$path) {
            http_response_code(404);
            return;
        }
        try {
            $mime = $imagePath ? FileUploader::imageInfo($imagePath)['mime'] : 'application/pdf';
        } catch (RuntimeException $e) {
            http_response_code(404);
            return;
        }
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header("Content-Disposition: " . ($imagePath ? 'inline' : 'attachment') . "; filename*=UTF-8''" . rawurlencode($item['attachment_name']));
        session_write_close();
        readfile($path);
    }
}
