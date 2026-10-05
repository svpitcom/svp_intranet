<?php
class SharePointController extends Controller
{
    public function index(): void
    {
        $client = new SharePointClient();
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

    public function export(): void
    {
        try {
            $client = new SharePointClient();
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
            (new SharePointClient())->syncDeviceDemo($devices);
            Session::flash('success', 'อัปเดต Excel ทดสอบบน SharePoint แล้ว ' . count($devices) . ' รายการ');
        } catch (Throwable $e) {
            Session::flash('error', $e instanceof RuntimeException && !$e instanceof PDOException ? $e->getMessage() : 'อัปเดต Excel ไม่สำเร็จ กรุณาตรวจสอบไฟล์ปลายทางก่อนลองใหม่');
        }
        $this->redirect('/devices');
    }

    public function exportTables(): void
    {
        $type = $this->input('type', '');
        if ($type !== 'all' && !isset(SharePointTables::LABELS[$type])) {
            Session::flash('error', 'กรุณาเลือกประเภทข้อมูลที่ต้องการส่งออก');
            $this->redirect('/sharepoint');
            return;
        }
        $client = new SharePointClient();
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
                $client->exportTable($key, $rows);
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
