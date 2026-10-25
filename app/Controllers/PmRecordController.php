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
            'old' => Session::get('pm_form_old_'.$scheduleId, []),
        ]);
        Session::remove('pm_form_old_'.$scheduleId);
    }

    public function store(string $scheduleId): void
    {
        $scheduleId = (int) $scheduleId;
        $performedDate = $this->input('performed_date') ?: date('Y-m-d');

        $schedule=(new PmSchedule())->find($scheduleId);
        if (!$schedule) {
            Session::flash('error', 'ไม่พบแผน PM นี้');
            $this->redirect('/pm-schedules');
        }

        $inspection=null;
        if (isset($schedule['svp_device_id'])) {
            $schedule=(new PmSchedule())->findWithRelations($scheduleId);
            if (!$schedule) { Session::flash('error','ไม่พบอุปกรณ์ของแผนนี้'); $this->redirect('/pm-schedules'); }
            if (isset($_POST['inspection_device_id']) && ($schedule['asset_group']??'')!=='it') {
                Session::remove('pm_form_old_'.$scheduleId);
                Session::flash('error','กลุ่มอุปกรณ์เปลี่ยนแล้ว กรุณาเปิดแบบฟอร์มใหม่'); $this->redirect("/pm-schedules/{$scheduleId}/record");
            }
            if (($schedule['asset_group']??'')==='it') {
                if ($this->input('inspection_device_id')!==(string)$schedule['svp_device_id']) {
                    Session::remove('pm_form_old_'.$scheduleId);
                    Session::flash('error','อุปกรณ์ของแผนเปลี่ยนแล้วหรือแบบฟอร์มไม่ครบ กรุณาตรวจข้อมูลในแบบฟอร์มใหม่');
                    $this->redirect("/pm-schedules/{$scheduleId}/record");
                }
                // Retain a bounded draft only in this user's session on validation failure.
                Session::set('pm_form_old_'.$scheduleId, PmInspection::restore($_POST,PmInspection::defaults($schedule)));
                try { $inspection=PmInspection::validate($_POST,$schedule); }
                catch (RuntimeException $e) { Session::flash('error',$e->getMessage()); $this->redirect("/pm-schedules/{$scheduleId}/record"); }
            }
        }
        if (($inspection!==null && !$this->input('performed_date')) || !Validation::date($performedDate) || $performedDate > Portal::today()) {
            Session::flash('error', 'วันที่ทำ PM ไม่ถูกต้องหรือเป็นวันในอนาคต');
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
            $lock = $db->prepare('SELECT * FROM pm_schedule WHERE pm_schedule_id = ? FOR UPDATE');
            $lock->execute([$scheduleId]);
            $locked=$lock->fetch();
            if (!$locked || ($locked['svp_device_id']??null)!==($schedule['svp_device_id']??null)) throw new RuntimeException('Schedule changed; reload before saving');
            $recordId = (new PmRecord())->insert([
                'pm_schedule_id'            => $scheduleId,
                'performed_by'                => $this->currentUser()['svp_user_id'],
                'performed_date'              => $performedDate,
                'result_status'                => $this->input('result_status'),
                'notes'                         => $this->input('notes'),
                'attachment_original_name'   => $attachmentOriginalName,
                'attachment_path'             => $attachmentPath,
            ]);

            if ($inspection!==null) {
                $inspection['performed_date']=$performedDate;
                $inspection['result_status']=$this->input('result_status');
                $inspection['device_id']=$schedule['svp_device_id'];
                $inspection['pm_title']=$schedule['pm_title'];
                $inspection['revision']=PmInspection::REVISION;
                (new PmInspectionForm())->save($recordId,$inspection);
            }

            if ($this->input('result_status') === 'completed') {
                (new PmSchedule())->markCompleted($scheduleId, $performedDate);
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            FileUploader::discard(UPLOAD_PM_RECORD_PATH, $attachmentPath);
            error_log('PM record save failed: ' . $e->getMessage());
            Session::flash('error', 'บันทึกข้อมูลไม่สำเร็จ กรุณาลองอีกครั้ง');
            $this->redirect("/pm-schedules/{$scheduleId}/record");
        }

        Session::flash('success', 'บันทึกการทำ PM เรียบร้อยแล้ว');
        Session::remove('pm_form_old_'.$scheduleId);
        if ($attachmentPath !== null) {
            // Upload only after commit: remote failure must not discard a saved PM or PDF.
            $this->sendPdfToSharePoint($recordId, $attachmentPath);
        }
        $this->redirect($inspection!==null ? '/pm-records/'.$recordId.'/form' : '/pm-schedules');
    }

    public function form(string $id): void
    {
        $data=(new PmInspectionForm())->forRecord((int)$id);
        if (!$data) { http_response_code(404); echo 'ไม่พบแบบฟอร์ม IT-F-005 (ประวัติเดิมยังอยู่ในหน้าประวัติ PM)'; return; }
        $this->view('pm_records/inspection',['data'=>$data,'readOnly'=>true,'recordId'=>(int)$id,'history'=>[]]);
    }

    public function exportForm(string $id): void
    {
        $data=(new PmInspectionForm())->forRecord((int)$id);
        if (!$data) { http_response_code(404); echo 'ไม่พบแบบฟอร์ม IT-F-005'; return; }
        $format=$this->input('format','xlsx');
        if (!in_array($format,['xlsx','pdf'],true)) { http_response_code(400); echo 'รูปแบบไฟล์ไม่ถูกต้อง'; return; }
        $bytes=$format==='pdf' ? PmInspectionExport::pdf($data) : PmInspectionExport::xlsx($data);
        header('Content-Type: '.($format==='pdf'?'application/pdf':'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
        header('Content-Disposition: attachment; filename="IT-F-005-'.$id.'.'.$format.'"');
        header('Cache-Control: private, no-store');
        echo $bytes;
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
