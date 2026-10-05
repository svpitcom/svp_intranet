<?php
class PmRecordController extends Controller
{
    /** ประวัติการทำ PM ทั้งหมด (รายงาน) */
    public function index(): void
    {
        $records = (new PmRecord())->allWithRelations();
        $records = Search::rows($records, Search::term(), ['performed_date', 'svp_device_name', 'pm_title', 'first_name', 'last_name', 'result_status', 'notes', 'attachment_original_name']);
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

        if (!(new PmSchedule())->find($scheduleId)) {
            Session::flash('error', 'ไม่พบแผน PM นี้');
            $this->redirect('/pm-schedules');
        }
        if (!Validation::date($performedDate)) {
            Session::flash('error', 'วันที่ทำ PM ไม่ถูกต้อง');
            $this->redirect("/pm-schedules/{$scheduleId}/record");
        }
        if (!in_array($this->input('result_status'), ['completed', 'partial', 'issue_found'], true)) {
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

        $db = Database::connect();
        try {
            $db->beginTransaction();
            // Serialize records for the same schedule so an older completion cannot overwrite a newer one.
            $lock = $db->prepare('SELECT pm_schedule_id FROM pm_schedule WHERE pm_schedule_id = ? FOR UPDATE');
            $lock->execute([$scheduleId]);
            if (!$lock->fetch()) throw new RuntimeException('Schedule no longer exists');
            $recordId = (new PmRecord())->insert([
                'pm_schedule_id'            => $scheduleId,
                'performed_by'                => $this->currentUser()['svp_user_id'],
                'performed_date'              => $performedDate,
                'result_status'                => $this->input('result_status'),
                'notes'                         => $this->input('notes'),
                'attachment_original_name'   => $attachmentOriginalName,
                'attachment_path'             => $attachmentPath,
            ]);

            (new PmSchedule())->markCompleted($scheduleId, $performedDate);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            FileUploader::discard(UPLOAD_PM_RECORD_PATH, $attachmentPath);
            error_log('PM record save failed: ' . $e->getMessage());
            Session::flash('error', 'บันทึกข้อมูลไม่สำเร็จ กรุณาลองอีกครั้ง');
            $this->redirect("/pm-schedules/{$scheduleId}/record");
        }

        Session::flash('success', 'บันทึกการทำ PM เรียบร้อยแล้ว');
        if ($attachmentPath !== null) {
            // Upload only after commit: remote failure must not discard a saved PM or PDF.
            $this->sendPdfToSharePoint($recordId, $attachmentPath);
        }
        $this->redirect('/pm-schedules');
    }

    protected function sharePointClient(): SharePointClient
    {
        return new SharePointClient();
    }

    protected function sendPdfToSharePoint(int $recordId, string $attachmentPath): void
    {
        try {
            $this->sharePointClient()->uploadRecordPdf($recordId, $attachmentPath);
            Session::flash('success', 'บันทึก PM และส่งไฟล์ PDF ไป SharePoint เรียบร้อยแล้ว');
        } catch (Throwable $e) {
            Session::flash('error', 'บันทึก PM และ PDF ในระบบแล้ว แต่ส่ง PDF ไป SharePoint ไม่สำเร็จ ให้ผู้ดูแลส่งซ้ำจากหน้าประวัติ PM ไม่ต้องบันทึก PM ใหม่');
        }
    }

    public function retryPdf(int $id): void
    {
        $record = (new PmRecord())->find($id);
        if (!$record || empty($record['attachment_path'])) {
            Session::flash('error', 'ไม่พบรายการ PM หรือไฟล์ PDF แนบ');
        } else {
            $this->sendPdfToSharePoint($id, $record['attachment_path']);
        }
        $this->redirect('/pm-records');
    }
}
