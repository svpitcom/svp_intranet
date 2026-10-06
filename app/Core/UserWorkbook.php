<?php
/** Import/export rules for the separate SharePoint user-directory demo. */
class UserWorkbook
{
    private const COLUMNS = ['svp_user_id','username','first_name','last_name','email','svp_department_name','position_name','user_role','is_active'];
    private const HEADERS = ['รหัสผู้ใช้','ชื่อผู้ใช้','ชื่อ','นามสกุล','อีเมล','แผนก','ตำแหน่ง','สิทธิ์ (user/manager/admin)','สถานะ (1/0)'];

    public static function build(array $users): string
    {
        if (count($users) > 10000) throw new RuntimeException('รองรับไม่เกิน 10,000 บัญชี');
        $temp = tempnam(sys_get_temp_dir(), 'svp-users-');
        if ($temp === false) throw new RuntimeException('สร้างไฟล์ชั่วคราวไม่สำเร็จ');
        $path = $temp . '.zip';
        try {
            if (!copy(BASE_PATH . '/app/Templates/users-demo.xlsx', $path)) throw new RuntimeException('อ่านแม่แบบ Excel ผู้ใช้งานไม่สำเร็จ');
            $zip = new PharData($path);
            $doc = new DOMDocument();
            if (!$doc->loadXML($zip['xl/worksheets/sheet1.xml']->getContent(), LIBXML_NONET)) throw new RuntimeException('แม่แบบ Excel ผู้ใช้งานเสียหาย');
            $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            $xp = new DOMXPath($doc); $xp->registerNamespace('x', $ns);
            $data = $xp->query('//x:sheetData')->item(0);
            foreach (iterator_to_array($xp->query('//x:sheetData/x:row[number(@r)>=7]')) as $row) $data->removeChild($row);
            foreach ($users as $i => $user) {
                $r = $i + 7;
                $row = $doc->createElementNS($ns, 'x:row'); $row->setAttribute('r', (string) $r);
                foreach (self::COLUMNS as $j => $key) {
                    $cell = $doc->createElementNS($ns, 'x:c');
                    $cell->setAttribute('r', chr(65 + $j) . $r);
                    if (in_array($j, [0,8], true)) {
                        $cell->setAttribute('t', 'n');
                        $cell->appendChild($doc->createElementNS($ns, 'x:v', (string) (int) ($user[$key] ?? 0)));
                    } else {
                        $cell->setAttribute('t', 'inlineStr');
                        $is = $doc->createElementNS($ns, 'x:is'); $text = $doc->createElementNS($ns, 'x:t');
                        $text->setAttribute('xml:space', 'preserve');
                        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) ($user[$key] ?? ''));
                        $text->appendChild($doc->createTextNode(mb_substr($value, 0, 32767)));
                        $is->appendChild($text); $cell->appendChild($is);
                    }
                    $row->appendChild($cell);
                }
                $data->appendChild($row);
            }
            $zip['xl/worksheets/sheet1.xml'] = $doc->saveXML();
            foreach (new RecursiveIteratorIterator($zip) as $entry) {
                if (str_contains(str_replace('\\','/',$entry->getPathname()), '/xl/tables/')) {
                    $table = new DOMDocument(); $table->loadXML($entry->getContent(), LIBXML_NONET);
                    if ($table->documentElement->getAttribute('displayName') !== 'UserDirectoryDemo') continue;
                    $last = max(7, count($users) + 6);
                    $table->documentElement->setAttribute('ref', 'A6:I' . $last);
                    foreach ($table->getElementsByTagNameNS($ns, 'autoFilter') as $filter) $filter->setAttribute('ref', 'A6:I' . $last);
                    $zip['xl/tables/' . $entry->getFilename()] = $table->saveXML();
                }
            }
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

    public static function parse(string $bytes): array
    {
        if ($bytes === '' || strlen($bytes) > 10 * 1024 * 1024 || substr($bytes, 0, 2) !== 'PK') throw new RuntimeException('ไฟล์ Excel ว่าง ใหญ่เกิน 10 MB หรือไม่ใช่ไฟล์ XLSX');
        $temp = tempnam(sys_get_temp_dir(), 'svp-user-import-');
        if ($temp === false) throw new RuntimeException('สร้างไฟล์ชั่วคราวไม่สำเร็จ');
        $path = $temp . '.zip';
        try {
            if (file_put_contents($path, $bytes) !== strlen($bytes)) throw new RuntimeException('บันทึกไฟล์ชั่วคราวไม่สำเร็จ');
            $zip = new PharData($path);
            if (!isset($zip['xl/worksheets/sheet1.xml'])) throw new RuntimeException('ไม่พบชีตทะเบียนผู้ใช้งาน');
            $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'; $shared = [];
            if (isset($zip['xl/sharedStrings.xml'])) {
                $strings = new DOMDocument();
                if (!$strings->loadXML($zip['xl/sharedStrings.xml']->getContent(), LIBXML_NONET)) throw new RuntimeException('ไฟล์ Excel มี shared strings ไม่ถูกต้อง');
                $sx = new DOMXPath($strings); $sx->registerNamespace('x', $ns);
                foreach ($sx->query('//x:si') as $si) $shared[] = $si->textContent;
            }
            $doc = new DOMDocument();
            if (!$doc->loadXML($zip['xl/worksheets/sheet1.xml']->getContent(), LIBXML_NONET)) throw new RuntimeException('ชีตทะเบียนผู้ใช้งานอ่านไม่ได้');
            $xp = new DOMXPath($doc); $xp->registerNamespace('x', $ns);
            $header = $xp->query('//x:sheetData/x:row[@r="6"]')->item(0);
            if (!$header) throw new RuntimeException('โครงสร้างไฟล์ไม่ตรงกับทะเบียนผู้ใช้งาน');
            $headers = [];
            foreach (range(0, 8) as $col) $headers[] = self::cellValue($xp, $header, $col, $shared);
            if ($headers !== self::HEADERS) throw new RuntimeException('หัวคอลัมน์เปลี่ยนไป กรุณากดอัปเดต Excel จาก Intranet ก่อนนำเข้า');
            $rows = [];
            foreach ($xp->query('//x:sheetData/x:row[number(@r)>=7]') as $row) {
                $values = [];
                foreach (range(0, 8) as $col) {
                    $cellRef = chr(65 + $col) . $row->getAttribute('r');
                    $cell = $xp->query('./x:c[@r="' . $cellRef . '"]', $row)->item(0);
                    if ($cell && $xp->query('./x:f', $cell)->length) throw new RuntimeException('ไม่รองรับสูตรในแถว ' . $row->getAttribute('r'));
                    $values[] = trim(self::cellValue($xp, $row, $col, $shared));
                }
                if (count(array_filter($values, static fn($v) => $v !== '')) === 0) continue;
                $rows[] = array_combine(self::COLUMNS, $values) + ['_row'=>(int)$row->getAttribute('r')];
                if (count($rows) > 10000) throw new RuntimeException('ไฟล์มีบัญชีเกิน 10,000 รายการ');
            }
            return $rows;
        } catch (Throwable $e) {
            if ($e instanceof RuntimeException) throw $e;
            throw new RuntimeException('อ่านไฟล์ Excel ผู้ใช้งานไม่สำเร็จ กรุณาบันทึกเป็น .xlsx แล้วลองใหม่');
        } finally {
            unset($zip);
            if (is_file($path)) unlink($path);
            unlink($temp);
        }
    }

    /** Update existing accounts only. New accounts must be created in Intranet so credentials are set safely. */
    public static function import(array $rows, PDO $db, int $actorId): array
    {
        if (!$rows) throw new RuntimeException('ไม่พบข้อมูลผู้ใช้งานในไฟล์');
        $departments = [];
        foreach ($db->query('SELECT svp_department_id, svp_department_name FROM department') as $r) $departments[trim($r['svp_department_name'])][] = (int)$r['svp_department_id'];
        $positions = [];
        foreach ($db->query('SELECT svp_position_id, position_name FROM position') as $r) $positions[trim($r['position_name'])][] = (int)$r['svp_position_id'];
        $existing = []; $usernames = []; $emails = [];
        foreach ($db->query('SELECT svp_user_id, username, email, user_role, is_active FROM users') as $r) {
            $id = (int)$r['svp_user_id']; $existing[$id] = $r;
            $usernames[strtolower(trim($r['username']))] = $id; $emails[strtolower(trim($r['email']))] = $id;
        }
        $seen = []; $actions = [];
        $resolve = static function (string $label, array $lookup, string $what, int $line): ?int {
            if ($label === '') return null;
            if (empty($lookup[$label])) throw new RuntimeException('แถว ' . $line . ': ไม่พบ' . $what . ' “' . mb_substr($label,0,60) . '”');
            if (count($lookup[$label]) !== 1) throw new RuntimeException('แถว ' . $line . ': ' . $what . ' ซ้ำ กรุณาตรวจชื่อใน Intranet');
            return $lookup[$label][0];
        };
        foreach ($rows as $index=>$row) {
            $line = (int)($row['_row'] ?? ($index+7)); $idText = trim((string)($row['svp_user_id'] ?? ''));
            if (!preg_match('/^[1-9][0-9]*$/D', $idText) || (string)(int)$idText !== $idText) throw new RuntimeException('แถว ' . $line . ': ต้องระบุรหัสผู้ใช้เดิมในคอลัมน์ A; หากเป็นบัญชีใหม่ ให้เพิ่มผ่านหน้า Intranet');
            $id = (int)$idText;
            if (isset($seen[$id])) throw new RuntimeException('แถว ' . $line . ': รหัสผู้ใช้ซ้ำในไฟล์');
            $seen[$id] = true;
            if (!isset($existing[$id])) throw new RuntimeException('แถว ' . $line . ': ไม่พบรหัสผู้ใช้ ' . $id . ' ใน Intranet; หากเป็นบัญชีใหม่ ให้เพิ่มผ่านหน้า Intranet (ทั้งชุดยังไม่ถูกบันทึก)');
            $username = trim((string)($row['username'] ?? '')); $first = trim((string)($row['first_name'] ?? '')); $last = trim((string)($row['last_name'] ?? '')); $email = trim((string)($row['email'] ?? ''));
            if ($username === '' || $first === '' || $last === '' || !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('แถว ' . $line . ': ชื่อผู้ใช้ ชื่อ นามสกุล และอีเมลที่ถูกต้องจำเป็นต้องกรอก');
            if (isset($usernames[strtolower($username)]) && $usernames[strtolower($username)] !== $id) throw new RuntimeException('แถว ' . $line . ': ชื่อผู้ใช้นี้ซ้ำใน Intranet');
            if (isset($emails[strtolower($email)]) && $emails[strtolower($email)] !== $id) throw new RuntimeException('แถว ' . $line . ': อีเมลนี้ซ้ำใน Intranet');
            $role = trim((string)($row['user_role'] ?? '')); $active = trim((string)($row['is_active'] ?? ''));
            if (!in_array($role,['user','manager','admin'],true)) throw new RuntimeException('แถว ' . $line . ': สิทธิ์ต้องเป็น user, manager หรือ admin');
            if (!in_array($active,['0','1'],true)) throw new RuntimeException('แถว ' . $line . ': สถานะต้องเป็น 0 หรือ 1');
            if ($id === $actorId && ($role !== 'admin' || $active !== '1')) throw new RuntimeException('แถว ' . $line . ': ไม่อนุญาตให้ปิดใช้งานหรือลดสิทธิ์บัญชีผู้ดูแลที่กำลังนำเข้า');
            $usernames[strtolower($username)]=$id; $emails[strtolower($email)]=$id;
            $actions[] = ['id'=>$id,'line'=>$line,'data'=>[
                'username'=>$username,'first_name'=>$first,'last_name'=>$last,'email'=>$email,
                'svp_department_id'=>$resolve(trim((string)($row['svp_department_name'] ?? '')),$departments,'แผนก',$line),
                'svp_position_id'=>$resolve(trim((string)($row['position_name'] ?? '')),$positions,'ตำแหน่ง',$line),
                'user_role'=>$role,'is_active'=>(int)$active,
            ]];
        }
        try {
            $db->beginTransaction();
            $update = $db->prepare('UPDATE users SET username=:username, first_name=:first_name, last_name=:last_name, email=:email, svp_department_id=:svp_department_id, svp_position_id=:svp_position_id, user_role=:user_role, is_active=:is_active WHERE svp_user_id=:id');
            $lock = $db->prepare('SELECT svp_user_id FROM users WHERE svp_user_id=? FOR UPDATE');
            foreach ($actions as $action) {
                $lock->execute([$action['id']]);
                if (!$lock->fetchColumn()) throw new RuntimeException('แถว ' . $action['line'] . ': บัญชีถูกลบระหว่างนำเข้า; ไม่มีรายการถูกบันทึก');
                $update->execute($action['data'] + ['id'=>$action['id']]);
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            if ($e instanceof RuntimeException) throw $e;
            throw new RuntimeException('นำเข้ารายชื่อไม่ได้ ข้อมูลเดิมยังไม่เปลี่ยน กรุณาตรวจข้อมูลซ้ำและความยาวช่อง');
        }
        return ['updated'=>count($actions)];
    }

    private static function cellValue(DOMXPath $xp, DOMNode $row, int $column, array $shared): string
    {
        $cell = $xp->query('./x:c[@r="' . chr(65+$column) . $row->getAttribute('r') . '"]',$row)->item(0);
        if (!$cell) return '';
        if ($cell->getAttribute('t') === 'inlineStr') return $xp->query('./x:is',$cell)->item(0)?->textContent ?? '';
        $value = $xp->query('./x:v',$cell)->item(0)?->textContent ?? '';
        return $cell->getAttribute('t') === 's' ? ($shared[(int)$value] ?? '') : $value;
    }
}
