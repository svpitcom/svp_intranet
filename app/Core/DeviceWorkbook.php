<?php
/** Rebuild only our dedicated demo workbook, never an arbitrary master file. */
class DeviceWorkbook
{
    private const COLUMNS = ['svp_device_id','svp_device_name','brand_name','model_name','serial_number','device_type_name','svp_department_name','first_name','last_name','is_active'];

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
            $keys = self::COLUMNS;
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

    /** Parse the specific demo sheet and support both inline and shared Excel strings. */
    public static function parse(string $bytes): array
    {
        if ($bytes === '' || strlen($bytes) > 10 * 1024 * 1024 || substr($bytes, 0, 2) !== "PK") throw new RuntimeException('ไฟล์ Excel ว่าง ใหญ่เกิน 10 MB หรือไม่ใช่ไฟล์ XLSX');
        $temp = tempnam(sys_get_temp_dir(), 'svp-import-');
        if ($temp === false) throw new RuntimeException('สร้างไฟล์ชั่วคราวไม่สำเร็จ');
        $path = $temp . '.zip';
        try {
            if (file_put_contents($path, $bytes) !== strlen($bytes)) throw new RuntimeException('บันทึกไฟล์ชั่วคราวไม่สำเร็จ');
            $zip = new PharData($path);
            if (!isset($zip['xl/worksheets/sheet1.xml'])) throw new RuntimeException('ไม่พบชีตทะเบียนอุปกรณ์');
            $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            $strings = [];
            if (isset($zip['xl/sharedStrings.xml'])) {
                $shared = new DOMDocument();
                if (!$shared->loadXML($zip['xl/sharedStrings.xml']->getContent(), LIBXML_NONET)) throw new RuntimeException('ไฟล์ Excel มี shared strings ไม่ถูกต้อง');
                $sx = new DOMXPath($shared); $sx->registerNamespace('x', $ns);
                foreach ($sx->query('//x:si') as $si) $strings[] = $si->textContent;
            }
            $doc = new DOMDocument();
            if (!$doc->loadXML($zip['xl/worksheets/sheet1.xml']->getContent(), LIBXML_NONET)) throw new RuntimeException('ชีตทะเบียนอุปกรณ์อ่านไม่ได้');
            $xp = new DOMXPath($doc); $xp->registerNamespace('x', $ns);
            $header = $xp->query('//x:sheetData/x:row[@r="6"]')->item(0);
            if (!$header) throw new RuntimeException('โครงสร้างไฟล์ไม่ตรงกับชีตทะเบียนอุปกรณ์');
            $expected = ['รหัสอุปกรณ์','ชื่ออุปกรณ์','ยี่ห้อ','รุ่น','Serial Number','ประเภท','แผนก','ชื่อผู้รับผิดชอบ','นามสกุล','ใช้งาน (1/0)'];
            $actual = [];
            foreach (range(0, 9) as $col) $actual[] = self::cellValue($xp, $header, $col, $strings);
            if ($actual !== $expected) throw new RuntimeException('หัวคอลัมน์ในไฟล์ Excel เปลี่ยนไป กรุณาอัปเดตไฟล์จาก Intranet ก่อนนำเข้า');
            $rows = [];
            foreach ($xp->query('//x:sheetData/x:row[number(@r)>=7]') as $row) {
                $values = [];
                foreach (range(0, 9) as $col) {
                    $letter = chr(65 + $col) . $row->getAttribute('r');
                    $cell = $xp->query('./x:c[@r="' . $letter . '"]', $row)->item(0);
                    if (!$cell) { $values[] = ''; continue; }
                    if ($xp->query('./x:f', $cell)->length) throw new RuntimeException('ไม่รองรับสูตรในแถว ' . $row->getAttribute('r') . ' กรุณาวางค่าเป็นข้อความหรือค่าคงที่');
                    $values[] = self::cellValue($xp, $row, $col, $strings);
                }
                if (count(array_filter($values, static fn($v) => trim((string) $v) !== '')) === 0) continue;
                $rows[] = array_combine(self::COLUMNS, array_map(static fn($v) => trim((string) $v), $values)) + ['_row' => (int) $row->getAttribute('r')];
                if (count($rows) > 10000) throw new RuntimeException('ไฟล์มีอุปกรณ์เกิน 10,000 รายการ');
            }
            return $rows;
        } catch (Throwable $e) {
            if ($e instanceof RuntimeException) throw $e;
            throw new RuntimeException('อ่านไฟล์ Excel ไม่สำเร็จ กรุณาเปิดและบันทึกเป็น .xlsx แล้วลองใหม่');
        } finally {
            unset($zip);
            if (is_file($path)) unlink($path);
            unlink($temp);
        }
    }

    /** Import rows atomically; blank ID inserts with database-generated ID, existing ID updates. */
    public static function import(array $rows, PDO $db): array
    {
        if (!$rows) throw new RuntimeException('ไม่พบข้อมูลอุปกรณ์ในไฟล์');
        $types = [];
        foreach ($db->query('SELECT device_type_id, device_type_name FROM device_type') as $r) $types[trim($r['device_type_name'])][] = (int) $r['device_type_id'];
        $departments = [];
        foreach ($db->query('SELECT svp_department_id, svp_department_name FROM department') as $r) $departments[trim($r['svp_department_name'])][] = (int) $r['svp_department_id'];
        $users = [];
        foreach ($db->query('SELECT svp_user_id, first_name, last_name FROM users') as $r) $users[trim($r['first_name'] . ' ' . $r['last_name'])][] = (int) $r['svp_user_id'];
        $seenIds = []; $actions = [];
        foreach ($rows as $index => $row) {
            $line = (int) ($row['_row'] ?? ($index + 7));
            $idText = trim((string) ($row['svp_device_id'] ?? ''));
            if ($idText !== '' && !preg_match('/^[1-9][0-9]*$/D', $idText)) throw new RuntimeException('แถว ' . $line . ': รหัสอุปกรณ์ต้องเป็นจำนวนเต็มบวกหรือเว้นว่างสำหรับอุปกรณ์ใหม่');
            $id = $idText === '' ? null : (int) $idText;
            if ($id !== null && isset($seenIds[$id])) throw new RuntimeException('แถว ' . $line . ': รหัสอุปกรณ์ซ้ำในไฟล์');
            if ($id !== null) $seenIds[$id] = true;
            $name = trim((string) ($row['svp_device_name'] ?? ''));
            $serial = trim((string) ($row['serial_number'] ?? ''));
            if ($name === '' || $serial === '') throw new RuntimeException('แถว ' . $line . ': ชื่ออุปกรณ์และ Serial Number จำเป็นต้องกรอก');
            if (!in_array((string) ($row['is_active'] ?? ''), ['0','1'], true)) throw new RuntimeException('แถว ' . $line . ': สถานะใช้งานต้องเป็น 0 หรือ 1');
            $resolve = static function (string $label, array $lookup, string $field, int $line): ?int {
                if ($label === '') return null;
                if (empty($lookup[$label])) throw new RuntimeException('แถว ' . $line . ': ไม่พบ' . $field . ' “' . mb_substr($label, 0, 60) . '” ใน Intranet');
                if (count($lookup[$label]) !== 1) throw new RuntimeException('แถว ' . $line . ': ' . $field . ' ชื่อซ้ำ กรุณาแก้จาก Intranet');
                return $lookup[$label][0];
            };
            $first = trim((string) ($row['first_name'] ?? '')); $last = trim((string) ($row['last_name'] ?? ''));
            if (($first === '') !== ($last === '')) throw new RuntimeException('แถว ' . $line . ': ชื่อและนามสกุลผู้รับผิดชอบต้องระบุพร้อมกัน');
            $owner = $first === '' ? null : $resolve($first . ' ' . $last, $users, 'ผู้รับผิดชอบ', $line);
            $payload = [
                'svp_device_name' => $name, 'brand_name' => trim((string) ($row['brand_name'] ?? '')),
                'model_name' => trim((string) ($row['model_name'] ?? '')), 'serial_number' => $serial,
                'device_type_id' => $resolve(trim((string) ($row['device_type_name'] ?? '')), $types, 'ประเภทอุปกรณ์', $line),
                'svp_department_id' => $resolve(trim((string) ($row['svp_department_name'] ?? '')), $departments, 'แผนก', $line),
                'svp_user_id' => $owner, 'is_active' => (int) $row['is_active'],
            ];
            $actions[] = ['id' => $id, 'payload' => $payload, 'line' => $line];
        }
        $updated = $created = 0;
        try {
            $db->beginTransaction();
            $update = $db->prepare('UPDATE device SET svp_device_name=:svp_device_name, brand_name=:brand_name, model_name=:model_name, serial_number=:serial_number, device_type_id=:device_type_id, svp_department_id=:svp_department_id, svp_user_id=:svp_user_id, is_active=:is_active WHERE svp_device_id=:id');
            $exists = $db->prepare('SELECT svp_device_id FROM device WHERE svp_device_id=? FOR UPDATE');
            $insert = $db->prepare('INSERT INTO device (svp_device_name, brand_name, model_name, serial_number, device_type_id, svp_department_id, svp_user_id, is_active) VALUES (:svp_device_name, :brand_name, :model_name, :serial_number, :device_type_id, :svp_department_id, :svp_user_id, :is_active)');
            foreach ($actions as $action) {
                if ($action['id'] === null) { $insert->execute($action['payload']); $created++; continue; }
                $exists->execute([$action['id']]);
                if (!$exists->fetchColumn()) throw new RuntimeException('แถว ' . $action['line'] . ': ไม่พบรหัสอุปกรณ์ ' . $action['id'] . ' ใน Intranet หากเป็นอุปกรณ์ใหม่ให้เว้นช่องรหัสอุปกรณ์ (คอลัมน์ A) ว่าง หากแก้ไขรายการเดิมให้ใช้รหัสจาก Intranet แล้วนำเข้าอีกครั้ง (ยังไม่ได้บันทึกทั้งชุด)');
                $update->execute($action['payload'] + ['id' => $action['id']]); $updated++;
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            if ($e instanceof RuntimeException) throw $e;
            throw new RuntimeException('นำเข้ารายการไม่ได้ ข้อมูลเดิมยังไม่เปลี่ยน กรุณาตรวจ Serial Number และข้อมูลที่ซ้ำกัน');
        }
        return ['updated' => $updated, 'created' => $created];
    }

    private static function cellValue(DOMXPath $xp, DOMNode $row, int $column, array $shared): string
    {
        $letter = chr(65 + $column);
        $cell = $xp->query('./x:c[@r="' . $letter . $row->getAttribute('r') . '"]', $row)->item(0);
        if (!$cell) return '';
        $type = $cell->getAttribute('t');
        if ($type === 'inlineStr') return $xp->query('./x:is', $cell)->item(0)?->textContent ?? '';
        $value = $xp->query('./x:v', $cell)->item(0)?->textContent ?? '';
        if ($type === 's') return $shared[(int) $value] ?? '';
        return $value;
    }
}
