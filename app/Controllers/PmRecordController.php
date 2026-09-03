<?php
class PmRecordController extends Controller
{
    /** ประวัติการทำ PM ทั้งหมด (รายงาน) */
    public function index(): void
    {
        $records = (new PmRecord())->allWithRelations();
        $this->view('pm_records/index', ['records' => $records]);
    }

    /** ฟอร์มบันทึกว่าทำ PM ตามแผนนี้เสร็จแล้ว */
    public function create(string $scheduleId): void
    {
        $schedule = (new PmSchedule())->findWithRelations((int) $scheduleId);
        if (!$schedule) {
            Session::flash('error', 'ไม่พบแผน PM นี้');
            $this->redirect('/pm-schedules');
        }

        $this->view('pm_records/create', [
            'schedule' => $schedule,
            'history'  => (new PmRecord())->forSchedule((int) $scheduleId),
        ]);
    }

    public function store(string $scheduleId): void
    {
        $scheduleId = (int) $scheduleId;
        $performedDate = $this->input('performed_date') ?: date('Y-m-d');

        if (!$this->input('result_status')) {
            Session::flash('error', 'กรุณาเลือกผลการทำ PM');
            $this->redirect("/pm-schedules/{$scheduleId}/record");
        }

        // ---- จัดการไฟล์ PDF แนบ ----
        $attachmentOriginalName = null;
        $attachmentPath = null;

        try {
            $uploaded = FileUploader::uploadPdf('pm_attachment', UPLOAD_PM_RECORD_PATH);
            if ($uploaded) {
                $attachmentOriginalName = $uploaded['original_name'];
                $attachmentPath = $uploaded['stored_path'];
            }
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect("/pm-schedules/{$scheduleId}/record");
        }

        (new PmRecord())->insert([
            'pm_schedule_id'            => $scheduleId,
            'performed_by'                => $this->currentUser()['svp_user_id'],
            'performed_date'              => $performedDate,
            'result_status'                => $this->input('result_status'),
            'notes'                         => $this->input('notes'),
            'attachment_original_name'   => $attachmentOriginalName,
            'attachment_path'             => $attachmentPath,
        ]);

        (new PmSchedule())->markCompleted($scheduleId, $performedDate);

        Session::flash('success', 'บันทึกการทำ PM เรียบร้อยแล้ว');
        $this->redirect('/pm-schedules');
    }
}
