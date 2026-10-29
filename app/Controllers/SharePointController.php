<?php
class SharePointController extends Controller
{
    protected function client(): SharePointClient
    {
        return new SharePointClient();
    }
    private function destinationClient(): SharePointClient
    {
        return (new SharePointClient())->forDepartment($this->input('department_target', ''));
    }
    private function itClient(): SharePointClient
    {
        return (new SharePointClient())->forDepartment('IT');
    }
    public function index(): void
    {
        $user = $this->currentUser();
        if (!SharePointAccess::allows($user)) {
            http_response_code(403);
            return;
        }
        if (!SharePointAccess::admin($user)) {
            $this->departmentIndex($user);
            return;
        }
        $client = $this->client();
        $missing = $client->missingSettings();
        $site = null;
        $page = ['items' => [], 'cursor' => ''];
        $error = null;
        $folder = $this->input('folder', '');
        if (!$missing) {
            try {
                $site = $client->site();
                $page = $client->files($folder, $this->input('cursor', ''));
            } catch (RuntimeException | InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
        }
        $this->view('sharepoint/index', compact('missing', 'site', 'page', 'error', 'folder') + ['canExport' => $client->canExport()]);
    }

    private function departmentIndex(array $user): void
    {
        $department = SharePointAccess::department($user);
        $label = SharePointAccess::LABELS[$department ?? ''] ?? 'แผนกของฉัน';
        $page = ['items' => [], 'cursor' => '', 'folder' => []];
        $error = '';
        try {
            if ($department === null) throw new RuntimeException('ยังไม่ได้ระบุแผนกหรือยังไม่ได้จับคู่พื้นที่ของแผนก กรุณาติดต่อผู้ดูแลระบบ');
            $client = $this->client();
            if ($client->missingSettings()) throw new RuntimeException('SharePoint ยังไม่พร้อมใช้งาน กรุณาติดต่อผู้ดูแลระบบ');
            // Never accept department_target, department_id, or a root from the browser.
            $page = $client->departmentFiles($department, $this->input('folder', ''), $this->input('cursor', ''));
        } catch (RuntimeException | InvalidArgumentException $e) {
            $error = $e->getMessage();
        }
        $this->view('sharepoint/department', compact('page', 'error', 'label', 'department'));
    }

    public function export(): void
    {
        try {
            $client = $this->destinationClient();
            if (!$client->canExport()) throw new RuntimeException('กรุณาตั้งค่าการเชื่อมต่อและโฟลเดอร์ส่งออกก่อน');
            $result = $client->exportRecords((new PmRecord())->allWithRelations());
            Session::flash('success', 'ส่งออกประวัติ PM ไปยัง SharePoint แล้ว: ' . ($result['name'] ?? 'รายงาน CSV'));
        } catch (Throwable $e) {
            Session::flash('error', $e instanceof RuntimeException && !$e instanceof PDOException ? $e->getMessage() : 'ส่งออกรายงานไม่สำเร็จ กรุณาตรวจสอบข้อมูลและลองใหม่');
        }
        $this->redirect('/sharepoint');
    }

    public function syncDevices(): void
    {
        try {
            $devices = (new Device())->allWithDevice();
            $this->itClient()->syncDeviceDemo($devices);
            Session::flash('success', 'อัปเดต Excel ทดสอบบน SharePoint แล้ว ' . count($devices) . ' รายการ');
        } catch (Throwable $e) {
            Session::flash('error', $e instanceof RuntimeException && !$e instanceof PDOException ? $e->getMessage() : 'อัปเดต Excel ไม่สำเร็จ กรุณาตรวจสอบไฟล์ปลายทางก่อนลองใหม่');
        }
        $this->redirect('/devices');
    }

    public function importDevices(): void
    {
        try {
            $client = $this->itClient();
            $download = $client->downloadDeviceDemo();
            $rows = DeviceWorkbook::parse($download['bytes']);
            $result = DeviceWorkbook::import($rows, Database::connect());
            $message = 'นำเข้าจาก Excel แล้ว: เพิ่ม ' . $result['created'] . ' รายการ, แก้ไข ' . $result['updated'] . ' รายการ';
            try {
                $client->syncDeviceDemo((new Device())->allWithDevice(), $download['eTag']);
                $message .= ' และอัปเดต Excel ให้แสดงรหัสใหม่จาก Intranet แล้ว';
                Session::flash('success', $message);
            } catch (Throwable $e) {
                Session::flash('error', $message . ' แต่ยังอัปเดต Excel กลับไม่สำเร็จ กรุณากด “อัปเดต Excel” ภายหลัง');
            }
        } catch (Throwable $e) {
            Session::flash('error', $e instanceof RuntimeException && !$e instanceof PDOException ? $e->getMessage() : 'นำเข้าจาก Excel ไม่สำเร็จ ข้อมูลใน Intranet ยังไม่เปลี่ยน');
        }
        $this->redirect('/devices');
    }

    public function syncUsers(): void
    {
        try {
            $users = (new User())->allWithDepartmentAndPosition();
            $this->itClient()->syncUserDemo($users);
            Session::flash('success', 'อัปเดต Excel รายชื่อผู้ใช้งาน DEMO แล้ว ' . count($users) . ' รายการ');
        } catch (Throwable $e) {
            Session::flash('error', $e instanceof RuntimeException && !$e instanceof PDOException ? $e->getMessage() : 'อัปเดต Excel ผู้ใช้งานไม่สำเร็จ');
        }
        $this->redirect('/users');
    }

    public function importUsers(): void
    {
        try {
            $client = $this->itClient();
            $download = $client->downloadUserDemo();
            $rows = UserWorkbook::parse($download['bytes']);
            $result = UserWorkbook::import($rows, Database::connect(), (int)$this->currentUser()['svp_user_id']);
            $message = 'นำเข้าการแก้ไขรายชื่อผู้ใช้งานแล้ว ' . $result['updated'] . ' รายการ';
            try {
                $client->syncUserDemo((new User())->allWithDepartmentAndPosition(), $download['eTag']);
                Session::flash('success', $message . ' และอัปเดต Excel กลับแล้ว');
            } catch (Throwable $e) {
                Session::flash('error', $message . ' แต่เขียน ID/ข้อมูลกลับ Excel ไม่สำเร็จ กรุณาตรวจไฟล์ก่อนกดอัปเดต');
            }
        } catch (Throwable $e) {
            Session::flash('error', $e instanceof RuntimeException && !$e instanceof PDOException ? $e->getMessage() : 'นำเข้ารายชื่อผู้ใช้งานไม่สำเร็จ ข้อมูลเดิมยังไม่เปลี่ยน');
        }
        $this->redirect('/users');
    }

    public function exportTables(): void
    {
        $type = $this->input('type', '');
        if ($type !== 'all' && !isset(SharePointTables::LABELS[$type])) {
            Session::flash('error', 'กรุณาเลือกประเภทข้อมูลที่ต้องการส่งออก');
            $this->redirect('/sharepoint');
            return;
        }
        try {
            $client = in_array($type, ['devices', 'device_types'], true) ? $this->itClient() : $this->destinationClient();
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect('/sharepoint');
            return;
        }
        if (!$client->canExport()) {
            Session::flash('error', 'กรุณาตั้งค่าการเชื่อมต่อและโฟลเดอร์ส่งออกก่อน');
            $this->redirect('/sharepoint');
            return;
        }
        $types = $type === 'all' ? array_keys(SharePointTables::LABELS) : [$type];
        $sent = $failed = [];
        foreach ($types as $key) {
            try {
                $rows = SharePointTables::rows($key);
                $target = $type === 'all' && in_array($key, ['devices', 'device_types'], true) ? $this->itClient() : $client;
                $target->exportTable($key, $rows);
                $sent[] = SharePointTables::LABELS[$key] . ' (' . count($rows) . ' รายการ)';
            } catch (Throwable $e) {
                $failed[] = SharePointTables::LABELS[$key];
            }
        }
        if ($sent) Session::flash('success', 'ส่งไป SharePoint แล้ว: ' . implode(', ', $sent));
        if ($failed) Session::flash('error', 'ส่งไม่สำเร็จหรือยังยืนยันผลไม่ได้: ' . implode(', ', $failed) . ' กรุณาตรวจโฟลเดอร์ปลายทางก่อนส่งซ้ำเฉพาะประเภทที่ขาด');
        $this->redirect('/sharepoint');
    }
}
