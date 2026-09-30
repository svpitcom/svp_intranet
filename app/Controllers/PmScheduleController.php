<?php
class PmScheduleController extends Controller
{
    public function index(): void
    {
        $schedules = (new PmSchedule())->allWithRelations();
        foreach ($schedules as &$s) {
            $s['pm_status'] = PmSchedule::statusFromDaysRemaining((int) $s['days_remaining']);
        }
        unset($s);
        $schedules = Search::rows($schedules, Search::term(), ['svp_device_name', 'serial_number', 'pm_title', 'frequency_days', 'first_name', 'last_name', 'next_pm_date', 'pm_status']);

        $this->view('pm_schedules/index', ['schedules' => $schedules]);
    }

    public function create(): void
    {
        $this->view('pm_schedules/form', [
            'schedule' => null,
            'devices'  => (new Device())->all(),
            'users'    => (new User())->all(),
        ]);
    }

    public function store(): void
    {
        $errors = $this->validate();
        if ($errors) {
            Session::flash('errors', implode(' / ', $errors));
            $this->redirect('/pm-schedules/create');
        }

        $refDoc = $this->handleUpload();
        if ($refDoc === false) {
            $this->redirect('/pm-schedules/create'); // error ถูก flash ไว้ใน handleUpload() แล้ว
        }

        $frequencyDays = (int) $this->input('frequency_days');
        $startDate = $this->input('start_date') ?: date('Y-m-d');

        try {
            (new PmSchedule())->insert([
                'svp_device_id'                => $this->input('svp_device_id'),
                'pm_title'                      => $this->input('pm_title'),
                'frequency_days'                => $frequencyDays,
                'checklist' => $this->buildChecklistText(),
                'responsible_user_id'           => $this->input('responsible_user_id') ?: null,
                'next_pm_date'                   => date('Y-m-d', strtotime("{$startDate} +{$frequencyDays} days")),
                'reference_doc_original_name'  => $refDoc['original_name'] ?? null,
                'reference_doc_path'            => $refDoc['stored_path'] ?? null,
                'is_active'                      => 1,
            ]);
        } catch (Throwable $e) {
            FileUploader::discard(UPLOAD_PM_SCHEDULE_PATH, $refDoc['stored_path'] ?? null);
            error_log('PM schedule save failed: ' . $e->getMessage());
            Session::flash('error', 'บันทึกแผน PM ไม่สำเร็จ กรุณาลองอีกครั้ง');
            $this->redirect('/pm-schedules/create');
        }

        Session::flash('success', 'สร้างแผน PM เรียบร้อยแล้ว');
        $this->redirect('/pm-schedules');
    }

    public function edit(string $id): void
    {
        $schedule = (new PmSchedule())->find((int) $id);
        if (!$schedule) {
            Session::flash('error', 'ไม่พบแผน PM ที่ต้องการแก้ไข');
            $this->redirect('/pm-schedules');
        }

        $this->view('pm_schedules/form', [
            'schedule' => $schedule,
            'devices'  => (new Device())->all(),
            'users'    => (new User())->all(),
        ]);
    }

    public function update(string $id): void
    {
        $id = (int) $id;
        if (!(new PmSchedule())->find($id)) {
            Session::flash('error', 'ไม่พบแผน PM นี้');
            $this->redirect('/pm-schedules');
        }
        $errors = $this->validate(true);
        if ($errors) {
            Session::flash('errors', implode(' / ', $errors));
            $this->redirect("/pm-schedules/{$id}/edit");
        }

        $refDoc = $this->handleUpload();
        if ($refDoc === false) {
            $this->redirect("/pm-schedules/{$id}/edit");
        }

        $data = [
            'svp_device_id'        => $this->input('svp_device_id'),
            'pm_title'              => $this->input('pm_title'),
            'frequency_days'        => (int) $this->input('frequency_days'),
            'checklist' => $this->buildChecklistText(),
            'responsible_user_id'   => $this->input('responsible_user_id') ?: null,
            'next_pm_date'           => $this->input('next_pm_date'),
            'is_active'              => $this->input('is_active') ? 1 : 0,
        ];

        // อัปเดตไฟล์อ้างอิงเฉพาะเมื่อมีการแนบไฟล์ใหม่มา (ไม่แนบใหม่ = คงไฟล์เดิมไว้)
        if ($refDoc) {
            $data['reference_doc_original_name'] = $refDoc['original_name'];
            $data['reference_doc_path'] = $refDoc['stored_path'];
        }

        $db = Database::connect();
        try {
            $db->beginTransaction();
            $lock = $db->prepare('SELECT reference_doc_path FROM pm_schedule WHERE pm_schedule_id = ? FOR UPDATE');
            $lock->execute([$id]);
            $previous = $lock->fetch();
            if (!$previous) throw new RuntimeException('Schedule no longer exists');
            (new PmSchedule())->update($id, $data);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            FileUploader::discard(UPLOAD_PM_SCHEDULE_PATH, $refDoc['stored_path'] ?? null);
            error_log('PM schedule update failed: ' . $e->getMessage());
            Session::flash('error', 'บันทึกแผน PM ไม่สำเร็จ กรุณาลองอีกครั้ง');
            $this->redirect("/pm-schedules/{$id}/edit");
        }
        if ($refDoc) FileUploader::discard(UPLOAD_PM_SCHEDULE_PATH, $previous['reference_doc_path']);

        Session::flash('success', 'บันทึกการแก้ไขเรียบร้อยแล้ว');
        $this->redirect('/pm-schedules');
    }

    public function destroy(string $id): void
    {
        $db = Database::connect();
        try {
            $db->beginTransaction();
            $lock = $db->prepare('SELECT reference_doc_path FROM pm_schedule WHERE pm_schedule_id = ? FOR UPDATE');
            $lock->execute([(int) $id]);
            $previous = $lock->fetch();
            if (!$previous) throw new RuntimeException('Schedule no longer exists');
            $attachments = (new PmRecord())->forSchedule((int) $id);
            (new PmSchedule())->delete((int) $id);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('PM schedule delete failed: ' . $e->getMessage());
            Session::flash('error', 'ลบแผน PM ไม่สำเร็จ แผนอาจมีประวัติที่ใช้งานอยู่');
            $this->redirect('/pm-schedules');
        }
        FileUploader::discard(UPLOAD_PM_SCHEDULE_PATH, $previous['reference_doc_path']);
        foreach ($attachments as $attachment) {
            // Only discard files if the database actually cascaded the record deletion.
            if (!(new PmRecord())->find((int) $attachment['pm_record_id'])) {
                FileUploader::discard(UPLOAD_PM_RECORD_PATH, $attachment['attachment_path']);
            }
        }
        Session::flash('success', 'ลบแผน PM เรียบร้อยแล้ว');
        $this->redirect('/pm-schedules');
    }

    /** จัดการอัปโหลดไฟล์อ้างอิง คืนค่า null = ไม่มีไฟล์แนบมา (ปกติ), false = error (ถูก flash ไว้แล้ว) */
    private function handleUpload()
    {
        try {
            return FileUploader::uploadPdf('reference_doc', UPLOAD_PM_SCHEDULE_PATH);
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            return false;
        }
    }

    private function validate(bool $isUpdate = false): array
    {
        $errors = [];
        if (!$this->input('svp_device_id')) $errors[] = 'กรุณาเลือกอุปกรณ์';
        if (!$this->input('pm_title')) $errors[] = 'กรุณากรอกชื่อแผน PM';
        if (filter_var($this->input('frequency_days'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 36500]]) === false) {
            $errors[] = 'กรุณากรอกรอบความถี่เป็นจำนวนวัน (มากกว่า 0)';
        }
        $date = $isUpdate ? $this->input('next_pm_date', '') : ($this->input('start_date') ?: date('Y-m-d'));
        if (!Validation::date($date)) $errors[] = 'วันที่ไม่ถูกต้อง';
        return $errors;
    }

    /** รวมรายการ checklist[] จากฟอร์ม (array) กลับเป็นข้อความคั่นบรรทัดเดียว สำหรับเก็บลง DB */
    private function buildChecklistText(): string
    {
        $items = $_POST['checklist'] ?? [];
        if (!is_array($items)) {
            return '';
        }

        $items = array_map('trim', array_filter($items, 'is_string'));
        $items = array_filter($items, fn($item) => $item !== '');

        return implode("\n", $items);
    }
}
