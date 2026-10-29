<?php
final class BackupChecklistExport
{
    private static function excelDate(string $date): int
    {
        return (int)(new DateTimeImmutable('1899-12-30', new DateTimeZone('UTC')))->diff(new DateTimeImmutable($date, new DateTimeZone('UTC')))->format('%a');
    }

    public static function xlsx(array $data): string
    {
        $values = ['A1' => 'Location : ' . $data['location'], 'A2' => 'ประจำเดือน ' . $data['month'], 'B1' => BackupChecklist::TITLE . ' ' . $data['server_name'], 'D37' => $data['prepared_by'], 'D38' => $data['prepared_date'] === '' ? '' : self::excelDate($data['prepared_date']), 'G37' => 'Approve By : ' . $data['approved_by'], 'G38' => 'Date : ' . $data['approved_date']];
        $formats = ['D38' => 'dd/mm/yyyy'];
        $heights = [];
        $lines = static fn(string $v, int $width) => array_sum(array_map(static fn($part) => max(1, (int)ceil(mb_strlen($part) / $width)), explode("\n", $v)));
        for ($day = 1; $day <= 31; $day++) {
            $r = $day + 5;
            $entry = $data['days'][$day] ?? null;
            $values['A' . $r] = $entry ? self::excelDate($data['month'] . '-' . sprintf('%02d', $day)) : '';
            $formats['A' . $r] = 'dd/mm/yyyy';
            foreach (['B' => 'data', 'C' => 'data_full', 'D' => 'start', 'E' => 'end', 'F' => 'status', 'G' => 'checked_by', 'H' => 'notes'] as $column => $key) {
                $value = $entry[$key] ?? '';
                if (in_array($key, ['start', 'end'], true)) {
                    $formats[$column . $r] = 'hh:mm:ss';
                    if ($value !== '') {
                        $parts = array_map('intval', explode(':', $value));
                        $value = ($parts[0] * 3600 + $parts[1] * 60 + ($parts[2] ?? 0)) / 86400;
                    }
                }
                $values[$column . $r] = $value;
            }
            if ($entry) $heights[$r] = max(20, 6 + 20 * max($lines($entry['data'], 8), $lines($entry['data_full'], 9), $lines($entry['status'], 8), $lines($entry['checked_by'], 16), $lines($entry['notes'], 20)));
        }
        $heights[1] = max(26, 14 * $lines($data['location'], 25));
        $heights[37] = max(28, 6 + 20 * max($lines($data['prepared_by'], 28), $lines($values['G37'], 30)));
        return FormTemplateWorkbook::fill(BASE_PATH . '/app/Templates/it-backup-checklist.xlsx', $values, $formats, $heights);
    }

    public static function pdf(array $data): string
    {
        ob_start();
        require BASE_PATH . '/app/Views/exports/backup_checklist_pdf.php';
        $html = ob_get_clean();
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->setChroot(BASE_PATH . '/public');
        $options->set('isFontSubsettingEnabled', true);
        $cache = sys_get_temp_dir() . '/svp-backup-pdf-fonts';
        if (!is_dir($cache) && !mkdir($cache, 0700, true) && !is_dir($cache)) throw new RuntimeException('สร้างโฟลเดอร์ฟอนต์ PDF ไม่สำเร็จ');
        $options->setFontDir($cache);
        $options->setFontCache($cache);
        $pdf = new \Dompdf\Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        return $pdf->output();
    }
}
