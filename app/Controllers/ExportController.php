<?php

use Dompdf\Dompdf;
use Dompdf\Options;

class ExportController extends Controller
{
    /** Export รายการแผน PM ทั้งหมดเป็น PDF */
    public function schedules(): void
    {
        $schedules = (new PmSchedule())->allWithRelations();
        foreach ($schedules as &$s) {
            $s['pm_status'] = PmSchedule::statusFromDaysRemaining((int) $s['days_remaining']);
        }
        unset($s);

        $html = $this->renderHtml('exports/pm_schedules_pdf', ['schedules' => $schedules]);
        $this->streamPdf($html, 'pm-schedules-' . date('Y-m-d') . '.pdf');
    }

    /** Export ประวัติการทำ PM ทั้งหมดเป็น PDF */
    public function records(): void
    {
        $records = (new PmRecord())->allWithRelations();
        $html = $this->renderHtml('exports/pm_records_pdf', ['records' => $records]);
        $this->streamPdf($html, 'pm-records-' . date('Y-m-d') . '.pdf');
    }

    /** เรนเดอร์ view (.php) ให้ออกมาเป็น HTML string แทนที่จะ echo ตรงๆ */
    private function renderHtml(string $view, array $data = []): string
    {
        extract($data);
        ob_start();
        require BASE_PATH . "/app/Views/{$view}.php";
        return ob_get_clean();
    }

    private function streamPdf(string $html, string $filename): void
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isFontSubsettingEnabled', true); // ลดขนาดไฟล์ PDF โดยฝังเฉพาะตัวอักษรที่ใช้จริง
        // ลบบรรทัด $options->set('defaultFont', 'Sarabun'); ออก — ไม่จำเป็นแล้วเพราะกำหนดผ่าน @font-face ในไฟล์ view โดยตรง

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    /** Export แบบฟอร์มแผน PM รายการเดียว (สำหรับพิมพ์เก็บเป็นเอกสาร) */
    public function scheduleForm(string $id): void
    {
        $schedule = (new PmSchedule())->findWithRelations((int) $id);

        if (!$schedule) {
            Session::flash('error', 'ไม่พบแผน PM นี้');
            $this->redirect('/pm-schedules');
        }

        $html = $this->renderHtml('exports/pm_schedule_form_pdf', ['schedule' => $schedule]);
        $filename = 'PM-Form-' . $schedule['pm_schedule_id'] . '-' . date('Y-m-d') . '.pdf';
        $this->streamPdf($html, $filename);
    }
}
