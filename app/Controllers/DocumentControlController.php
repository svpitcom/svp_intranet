<?php
class DocumentControlController extends Controller
{
    public function index(): void
    {
        $q = mb_substr($this->input('q', ''), 0, 200);
        $status = $this->input('status', '');
        $data = (new ControlledDocument())->listing($q, $status, (int)$this->input('page', '1'));
        $this->view('document_control/index', $data + compact('q', 'status'));
    }

    public function files(): void
    {
        $items = [];
        $cursor = '';
        $folder = [];
        $error = '';
        $documentId = max(0, (int)$this->input('document_id', '0'));
        try {
            $result = (new SharePointClient())->dccFiles($this->input('folder', ''), $this->input('cursor', ''));
            $items = $result['items'];
            $cursor = $result['cursor'];
            $folder = $result['folder'];
        } catch (RuntimeException | InvalidArgumentException $e) {
            $error = $e->getMessage();
        }
        $this->view('document_control/files', compact('items', 'cursor', 'folder', 'error', 'documentId'));
    }

    public function create(): void
    {
        $this->form(null);
    }
    public function edit(string $id): void
    {
        $this->form((int)$id);
    }

    private function form(?int $id): void
    {
        $model = new ControlledDocument();
        $document = $id === null ? ['code' => '', 'title' => '', 'revision' => '00', 'status' => 'draft', 'department_id' => '', 'effective_date' => '', 'review_date' => '', 'notes' => '', 'file_item_id' => '', 'file_name' => '', 'file_url' => '', 'version' => 0] : $model->find($id);
        if (!$document) {
            http_response_code(404);
            echo 'ไม่พบเอกสาร';
            return;
        }
        $error = '';
        if ($this->input('file', '') !== '') {
            try {
                $item = (new SharePointClient())->dccItem($this->input('file', ''));
                if (!isset($item['file'])) throw new RuntimeException('กรุณาเลือกไฟล์เอกสาร');
                $document['file_item_id'] = $item['id'];
                $document['file_name'] = $item['name'];
                $document['file_url'] = $item['webUrl'] ?? '';
                if ($id === null) $document['title'] = mb_substr(pathinfo($item['name'], PATHINFO_FILENAME), 0, 255);
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }
        $this->renderForm($document, $id, $error);
    }

    private function renderForm(array $document, ?int $id, string $error): void
    {
        $departments = (new Department())->all();
        $history = $id === null ? [] : (new ControlledDocument())->history($id);
        $this->view('document_control/form', compact('document', 'id', 'error', 'departments', 'history'));
    }

    public function store(): void
    {
        $this->save(null);
    }
    public function update(string $id): void
    {
        $this->save((int)$id);
    }

    private function save(?int $id): void
    {
        $data = [];
        foreach (['code', 'title', 'revision', 'status', 'effective_date', 'review_date', 'notes', 'file_item_id'] as $key) $data[$key] = $this->input($key, '');
        $data['department_id'] = $this->input('department_id', '');
        $version = (int)$this->input('version', '0');
        $data['file_name'] = '';
        $data['file_url'] = '';
        try {
            $errors = DocumentControlInput::validate($data);
            if ($errors) throw new RuntimeException(implode(' / ', $errors));
            if ($data['department_id'] !== '' && (!ctype_digit($data['department_id']) || !(new Department())->find((int)$data['department_id']))) throw new RuntimeException('ไม่พบแผนกที่เลือก');
            if ($data['file_item_id'] !== '') {
                $item = (new SharePointClient())->dccItem($data['file_item_id']);
                if (!isset($item['file']) || !DocumentControlInput::safeUrl($item['webUrl'] ?? '')) throw new RuntimeException('ไม่พบไฟล์เอกสาร DCC ที่เปิดได้');
                $data['file_name'] = $item['name'];
                $data['file_url'] = $item['webUrl'];
            }
            foreach (['department_id', 'effective_date', 'review_date'] as $key) if ($data[$key] === '') $data[$key] = null;
            $savedId = (new ControlledDocument())->saveDocument($id, $data, $version, (int)$this->currentUser()['svp_user_id']);
            Session::flash('success', 'บันทึกทะเบียนเอกสารและประวัติการแก้ไขแล้ว');
            $this->redirect('/document-control/' . $savedId . '/edit');
        } catch (RuntimeException $e) {
            $data['version'] = $version;
            $this->renderForm($data, $id, $e instanceof PDOException ? 'บันทึกทะเบียนเอกสารไม่สำเร็จ' : $e->getMessage());
        }
    }
}
