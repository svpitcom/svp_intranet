<?php
class AnnouncementController extends Controller
{
    public function index(): void
    {
        $q = mb_substr($this->input('q', ''), 0, 200);
        $manage = $this->input('manage', '') === '1' && Portal::manager($this->currentUser());
        $this->view('announcements/index', (new Announcement())->listing($this->currentUser(), $q, (int)$this->input('page', '1'), $manage) + compact('q', 'manage'));
    }
    public function show(string $id): void
    {
        try {
            $model = new Announcement();
            $item = $model->detail((int)$id, $this->currentUser());
        } catch (RuntimeException $e) {
            http_response_code(404);
            echo 'ไม่พบประกาศ';
            return;
        }
        $this->view('announcements/show', ['item' => $item, 'readers' => Portal::manager($this->currentUser()) ? $model->readers((int)$id) : []]);
    }
    public function create(): void
    {
        $this->form(null);
    }
    public function edit(string $id): void
    {
        $this->form((int)$id);
    }
    private function form(?int $id, ?array $data = null, string $error = ''): void
    {
        if (!Portal::manager($this->currentUser())) {
            http_response_code(403);
            return;
        }
        if ($data === null) {
            if ($id) {
                try {
                    $data = (new Announcement())->detail($id, $this->currentUser());
                } catch (RuntimeException $e) {
                    http_response_code(404);
                    return;
                }
            } else $data = ['publish_date' => Portal::today(), 'status' => 'draft', 'version' => 0];
        }
        $this->view('announcements/form', compact('id', 'data', 'error') + ['departments' => (new Department())->all()]);
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
        foreach (['title', 'body', 'department_id', 'status', 'publish_date', 'expires_date', 'pinned', 'version'] as $key) $data[$key] = $this->input($key, '');
        try {
            $saved = (new Announcement())->saveAnnouncement($id, $data, $this->currentUser(), (int)$data['version']);
        } catch (RuntimeException $e) {
            http_response_code(422);
            $this->form($id, $data, $e instanceof PDOException ? 'บันทึกไม่สำเร็จ' : $e->getMessage());
            return;
        }
        Session::flash('success', 'บันทึกประกาศแล้ว');
        $this->redirect('/announcements/' . $saved);
    }
    public function acknowledge(string $id): void
    {
        try {
            (new Announcement())->acknowledge((int)$id, $this->currentUser(), (int)$this->input('version', '0'));
            Session::flash('success', 'บันทึกการรับทราบแล้ว');
        } catch (RuntimeException $e) {
            Session::flash('error', $e instanceof PDOException ? 'บันทึกไม่สำเร็จ' : $e->getMessage());
        }
        $this->redirect('/announcements/' . (int)$id);
    }
}
