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

        (new PmSchedule())->insert([
            'svp_device_id'                => $this->input('svp_device_id'),
            'pm_title'                      => $this->input('pm_title'),
            'frequency_days'                => $frequencyDays,
            'checklist'                      => $this->input('checklist'),
            'responsible_user_id'           => $this->input('responsible_user_id') ?: null,
            'next_pm_date'                   => date('Y-m-d', strtotime("{$startDate} +{$frequencyDays} days")),
            'reference_doc_original_name'  => $refDoc['original_name'] ?? null,
            'reference_doc_path'            => $refDoc['stored_path'] ?? null,
            'is_active'                      => 1,
        ]);

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
        $errors = $this->validate();
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
            'checklist'              => $this->input('checklist'),
            'responsible_user_id'   => $this->input('responsible_user_id') ?: null,
            'next_pm_date'           => $this->input('next_pm_date'),
            'is_active'              => $this->input('is_active') ? 1 : 0,
        ];

        // อัปเดตไฟล์อ้างอิงเฉพาะเมื่อมีการแนบไฟล์ใหม่มา (ไม่แนบใหม่ = คงไฟล์เดิมไว้)
        if ($refDoc) {
            $data['reference_doc_original_name'] = $refDoc['original_name'];
            $data['reference_doc_path'] = $refDoc['stored_path'];
        }

        (new PmSchedule())->update($id, $data);

        Session::flash('success', 'บันทึกการแก้ไขเรียบร้อยแล้ว');
        $this->redirect('/pm-schedules');
    }

    public function destroy(string $id): void
    {
        (new PmSchedule())->delete((int) $id);
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

    private function validate(): array
    {
        $errors = [];
        if (!$this->input('svp_device_id')) $errors[] = 'กรุณาเลือกอุปกรณ์';
        if (!$this->input('pm_title')) $errors[] = 'กรุณากรอกชื่อแผน PM';
        if (!$this->input('frequency_days') || (int) $this->input('frequency_days') < 1) {
            $errors[] = 'กรุณากรอกรอบความถี่เป็นจำนวนวัน (มากกว่า 0)';
        }
        return $errors;
    }
}
