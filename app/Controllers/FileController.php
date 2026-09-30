<?php
class FileController extends Controller
{
    public function record(string $id): void
    {
        $row = (new PmRecord())->find((int) $id);
        $this->download(UPLOAD_PM_RECORD_PATH, $row['attachment_path'] ?? null, $row['attachment_original_name'] ?? 'report.pdf');
    }

    public function schedule(string $id): void
    {
        $row = (new PmSchedule())->find((int) $id);
        $this->download(UPLOAD_PM_SCHEDULE_PATH, $row['reference_doc_path'] ?? null, $row['reference_doc_original_name'] ?? 'reference.pdf');
    }

    private function download(string $folder, ?string $name, string $original): void
    {
        $path = FileUploader::resolve($folder, $name);
        $stream = $path === null ? false : fopen($path, 'rb');
        if ($stream === false) {
            http_response_code(404);
            echo 'ไม่พบไฟล์แนบ';
            return;
        }
        header('Content-Type: application/pdf');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header("Content-Disposition: attachment; filename=\"document.pdf\"; filename*=UTF-8''" . rawurlencode($original));
        header('Content-Length: ' . fstat($stream)['size']);
        session_write_close();
        fpassthru($stream);
        fclose($stream);
    }
}
