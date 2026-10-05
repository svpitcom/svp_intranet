<?php
/** Rebuild only our dedicated demo workbook, never an arbitrary master file. */
class DeviceWorkbook
{
    public static function build(array $devices): string
    {
        if (count($devices) > 10000) throw new RuntimeException('รองรับไม่เกิน 10,000 อุปกรณ์');
        $temp = tempnam(sys_get_temp_dir(), 'svp-xlsx-');
        if ($temp === false) throw new RuntimeException('สร้างไฟล์ชั่วคราวไม่สำเร็จ');
        $path = $temp . '.zip';
        try {
            if (!copy(BASE_PATH . '/app/Templates/devices-demo.xlsx', $path)) throw new RuntimeException('อ่านแม่แบบ Excel ไม่สำเร็จ');
            $zip = new PharData($path);
            $doc = new DOMDocument();
            $doc->loadXML($zip['xl/worksheets/sheet1.xml']->getContent(), LIBXML_NONET);
            $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            $xp = new DOMXPath($doc); $xp->registerNamespace('x', $ns);
            $data = $xp->query('//x:sheetData')->item(0);
            foreach (iterator_to_array($xp->query('//x:sheetData/x:row[number(@r)>=7]')) as $row) $data->removeChild($row);
            $xp->query('//x:c[@r="A4"]/x:v')->item(0)->textContent = 'อัปเดตจาก Intranet: ' . (new DateTimeImmutable('now', new DateTimeZone('Asia/Bangkok')))->format('d/m/Y H:i:s') . ' (กดปุ่มเพื่ออัปเดต)';
            $keys = ['svp_device_id','svp_device_name','brand_name','model_name','serial_number','device_type_name','svp_department_name','first_name','last_name','is_active'];
            foreach ($devices as $i => $device) {
                $r = $i + 7;
                $row = $doc->createElementNS($ns, 'x:row'); $row->setAttribute('r', (string) $r);
                $row->setAttribute('ht', '45'); $row->setAttribute('customHeight', '1');
                foreach ($keys as $j => $key) {
                    $cell = $doc->createElementNS($ns, 'x:c');
                    $cell->setAttribute('r', chr(65 + $j) . $r);
                    $cell->setAttribute('s', in_array($j, [0,4], true) ? '2' : '1');
                    $cell->setAttribute('t', $j === 9 ? 'n' : 'inlineStr');
                    if ($j === 9) $cell->appendChild($doc->createElementNS($ns, 'x:v', (string) (int) ($device[$key] ?? 0)));
                    else {
                        $is = $doc->createElementNS($ns, 'x:is'); $text = $doc->createElementNS($ns, 'x:t');
                        $text->setAttribute('xml:space', 'preserve');
                        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) ($device[$key] ?? ''));
                        $text->appendChild($doc->createTextNode(mb_substr($value, 0, 32767)));
                        $is->appendChild($text); $cell->appendChild($is);
                    }
                    $row->appendChild($cell);
                }
                $data->appendChild($row);
            }
            $zip['xl/worksheets/sheet1.xml'] = $doc->saveXML();
            $last = max(7, count($devices) + 6);
            $updates = [];
            foreach (new RecursiveIteratorIterator($zip) as $entry) {
                if (!preg_match('~/xl/tables/[^/]+\.xml$~', str_replace('\\', '/', $entry->getPathname()))) continue;
                $table = new DOMDocument(); $table->loadXML($entry->getContent(), LIBXML_NONET);
                $table->documentElement->setAttribute('ref', 'A6:J' . $last);
                foreach ($table->getElementsByTagNameNS($ns, 'autoFilter') as $filter) $filter->setAttribute('ref', 'A6:J' . $last);
                $updates['xl/tables/' . $entry->getFilename()] = $table->saveXML();
            }
            foreach ($updates as $name => $xml) $zip[$name] = $xml;
            unset($entry, $zip);
            $bytes = file_get_contents($path);
            if ($bytes === false || strlen($bytes) > 10 * 1024 * 1024) throw new RuntimeException('ไฟล์ Excel เกิน 10 MB หรืออ่านไม่สำเร็จ');
            return $bytes;
        } finally {
            unset($zip, $entry);
            if (is_file($path)) unlink($path);
            unlink($temp);
        }
    }
}
