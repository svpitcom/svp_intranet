<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/vendor/autoload.php';
define('BASE_PATH', dirname(__DIR__));
$cache = sys_get_temp_dir() . '/svp-pdf-' . bin2hex(random_bytes(8));
mkdir($cache);
$schedule = ['svp_device_name' => 'เครื่องทดสอบ', 'pm_title' => 'ตรวจเช็คอุปกรณ์', 'frequency_days' => 30, 'next_pm_date' => '2026-10-30', 'pm_status' => 'normal'];
$schedules = [$schedule];
$records = [['performed_date' => '2026-09-30', 'svp_device_name' => 'เครื่องทดสอบ', 'pm_title' => 'ตรวจเช็คอุปกรณ์', 'first_name' => 'ผู้', 'last_name' => 'ทดสอบ', 'result_status' => 'completed', 'notes' => 'ทดสอบภาษาไทย']];
$checklistItems = ['ตรวจสอบสภาพทั่วไป'];
$outputDir = $argv[1] ?? null;
if ($outputDir !== null && !is_dir($outputDir)) mkdir($outputDir, 0755, true);
try {
    foreach (['pm_records_pdf', 'pm_schedules_pdf', 'pm_schedule_form_pdf'] as $template) {
        ob_start();
        require BASE_PATH . '/app/Views/exports/' . $template . '.php';
        $html = ob_get_clean();
        $options = new Dompdf\Options();
        $options->setIsRemoteEnabled(false);
        $options->setChroot(BASE_PATH . '/public');
        $options->setFontDir($cache);
        $options->setFontCache($cache);
        $dompdf = new Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $template === 'pm_schedule_form_pdf' ? 'portrait' : 'landscape');
        $dompdf->render();
        $pdf = $dompdf->output();
        if (!str_starts_with($pdf, '%PDF-') || !str_contains($pdf, 'Sarabun')) throw new RuntimeException('PDF or Thai font missing: ' . $template);
        if (!str_contains($pdf, 'Sarabun-Bold')) throw new RuntimeException('Thai bold font missing: ' . $template);
        if (str_contains($pdf, '/BaseFont /Helvetica-Bold')) throw new RuntimeException('Unexpected Latin-only bold fallback: ' . $template);
        if ($outputDir !== null) file_put_contents($outputDir . '/' . $template . '.pdf', $pdf);
        echo 'PASS: ' . $template . ' rendered with embedded Sarabun font' . PHP_EOL;
        unset($dompdf);
        gc_collect_cycles();
    }
} finally {
    foreach (new DirectoryIterator($cache) as $file) {
        if ($file->isFile()) unlink($file->getPathname());
    }
    unset($file);
    rmdir($cache);
}
