<?php
final class PmInspectionExport
{
    public static function xlsx(array $data): string
    {
        $values = [
            'C5' => $data['device_name'],
            'C6' => $data['serial_number'],
            'C7' => $data['department'],
            'C8' => $data['performed_date'],
            'K6' => $data['manager'],
            'L6' => $data['inspector'],
            'A9' => 'เวลาตรวจสอบ ' . $data['start_time'] . ' ถึง ' . $data['end_time'] . ' น.',
            'F9' => 'รวม ' . ($data['start_time'] !== '' && $data['end_time'] !== '' ? PmInspection::minutes($data['start_time'], $data['end_time']) : '') . ' นาที · ผู้ใช้งานรับทราบ ' . $data['acknowledged_by'],
            'B78' => ($data['notes'] ?? '') === '' ? '' : 'หมายเหตุ: ' . $data['notes'],
            'F78' => 'สรุปผล: ' . (['completed' => 'ทำครบ / ปกติดี', 'partial' => 'ทำได้บางส่วน', 'issue_found' => 'พบปัญหา'][$data['result_status']] ?? 'ยังไม่ได้บันทึกผล'),
            'F79' => $data['revision'] ?? PmInspection::REVISION,
            'L79' => ''
        ];
        $merges = ['C5:F5', 'C6:F6', 'C7:F7', 'C8:F8', 'K6:K8', 'L6:L8', 'A9:E9', 'F9:J9', 'F78:L78', 'A79:E79', 'F79:L79'];
        $heights = [5 => 28, 6 => 28, 7 => 28, 8 => 24, 9 => 36, 78 => 40, 79 => 24];
        $lines = static fn($text, $width) => array_sum(array_map(static fn($v) => max(1, (int)ceil(mb_strlen($v) / $width)), explode("\n", $text)));
        foreach (PmInspection::SECTIONS as $section => [$title, $start, $end, $fields]) {
            for ($r = $start; $r <= $end; $r++) {
                $entry = $data[$section][$r];
                if ($section === 'checks') {
                    $merges[] = 'B' . $r . ':C' . $r;
                    $merges[] = 'D' . $r . ':E' . $r;
                    foreach (['B' => 'topic', 'D' => 'method', 'F' => 'standard'] as $column => $key) $values[$column . $r] = $entry[$key];
                    $values['G' . $r] = $entry['status'] === 'present' ? '✓' : '';
                    $values['H' . $r] = $entry['status'] === 'absent' ? '✓' : '';
                } elseif ($section === 'work') {
                    $values['B' . $r] = $entry['topic'];
                    $values['F' . $r] = $entry['detail'];
                    $values['G' . $r] = $entry['status'] === 'done' ? '✓' : '';
                    $values['H' . $r] = $entry['status'] === 'pending' ? '✓' : '';
                } else {
                    $merges[] = 'B' . $r . ':F' . $r;
                    $values['B' . $r] = $entry['topic'];
                    $values['G' . $r] = $entry['status'];
                }
                $merges[] = 'K' . $r . ':L' . $r;
                $values['I' . $r] = $entry['result'] === 'normal' ? '✓' : '';
                $values['J' . $r] = $entry['result'] === 'abnormal' ? '✓' : '';
                $values['K' . $r] = $entry['notes'];
                if (implode('', $entry) !== '') $heights[$r] = max(24, 6 + 16 * max(array_map(static fn($v) => $lines($v, 18), $entry)));
            }
        }
        $heights[5] = max(28, 16 * $lines($data['device_name'], 55));
        $heights[7] = max(28, 16 * $lines($data['department'], 55));
        $heights[6] = max(28, 8 * $lines($data['inspector'], 16), 8 * $lines($data['manager'], 16));
        $heights[9] = max(36, 16 * $lines($values['F9'], 40));
        // The original notes box spans B:E only; allow long notes to flow on a separate worksheet row height.
        $heights[78] = min(409, max(40, 16 * $lines($values['B78'], 35)));
        return FormTemplateWorkbook::fill(
            BASE_PATH . '/app/Templates/it-pm-inspection.xlsx',
            $values,
            [],
            $heights,
            ['merges' => $merges, 'cells' => ['B15' => 'B14', 'B77' => 'B76', 'K77' => 'K76', 'F79' => 'L79']]
        );
    }
    public static function pdf(array $data): string
    {
        ob_start();
        require BASE_PATH . '/app/Views/exports/pm_inspection_pdf.php';
        $html = ob_get_clean();
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->setChroot(BASE_PATH . '/public');
        $options->set('isFontSubsettingEnabled', true);
        $cache = sys_get_temp_dir() . '/svp-pm-inspection-fonts';
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
