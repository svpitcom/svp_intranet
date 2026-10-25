<?php
/** IT-F-005 fields use the original workbook row numbers as stable identifiers. */
final class PmInspection
{
    public const TITLE = 'ใบรายงานผลการตรวจเช็คอุปกรณ์คอมพิวเตอร์';
    public const REVISION = 'IT-F-005 Rev.00 Issue date:17-03-2025';
    public const MAIN = [
        13=>['ทำความสะอาดภายนอก','เช็คหน้าจอ'],
        14=>['ทำความสะอาดภายนอก','เช็คแป้นคีย์บอร์ด'],
        15=>['ทำความสะอาดภายนอก','จัดฝุ่นรอบ'],
        20=>['Window Update','Setting to window'],
        23=>['Update Bios','Update Bios'],
        25=>['Window Virus','Scan virus'],
        28=>['ค้นหา Update Drive','Update Driver'],
        32=>['ซ่อมแซ่มไฟล์','sfc /scannow'],
        35=>['ทดสอบรับสัญญาณเน็ต',''],
    ];
    public const SECTIONS = [
        'checks'=>['หัวข้อที่ทำการตรวจสอบ',12,50,['topic'=>'หัวข้อ','method'=>'วิธีการตรวจสอบ','standard'=>'มาตรฐาน','status'=>'สถานะ','result'=>'ผลการตรวจสอบ','notes'=>'รายละเอียด']],
        'work'=>['รายละเอียดการดำเนินงาน',53,57,['topic'=>'รายการ','detail'=>'รายละเอียดการดำเนินงาน','status'=>'สถานะ','result'=>'ผลการตรวจสอบ','notes'=>'หมายเหตุ']],
        'safety'=>['การตรวจสอบด้านความปลอดภัย',60,77,['topic'=>'รายละเอียดการตรวจสอบด้านความปลอดภัย','status'=>'สถานะ','result'=>'ผลการตรวจสอบ','notes'=>'หมายเหตุ']],
    ];
    public static function defaults(array $schedule, array $user = []): array
    {
        $data = ['inspection_device_id'=>(string)($schedule['svp_device_id']??''),'device_name'=>$schedule['svp_device_name']??'', 'serial_number'=>$schedule['serial_number']??'',
            'department'=>$schedule['svp_department_name']??'', 'inspector'=>trim(($user['first_name']??'').' '.($user['last_name']??'')),
            'manager'=>'','acknowledged_by'=>'','start_time'=>'','end_time'=>'','performed_date'=>'','result_status'=>'','notes'=>''];
        foreach (self::SECTIONS as $section=>[$title,$start,$end,$fields]) {
            for ($r=$start;$r<=$end;$r++) {
                $data[$section][$r]=array_fill_keys(array_keys($fields),'');
                if ($section==='checks' && isset(self::MAIN[$r])) {
                    [$data[$section][$r]['topic'],$data[$section][$r]['method']]=self::MAIN[$r];
                }
            }
        }
        return $data;
    }
    public static function options(string $section, string $key): ?array
    {
        if ($key==='result') return ['normal'=>'ปกติ','abnormal'=>'ผิดปกติ'];
        if ($key==='status' && $section==='checks') return ['present'=>'มี','absent'=>'ไม่มี'];
        if ($key==='status' && $section==='work') return ['done'=>'เสร็จ','pending'=>'ไม่เสร็จ'];
        return null;
    }
    public static function restore(array $raw,array $defaults): array
    {
        foreach($defaults as $key=>$value) {
            if (is_array($value)) $defaults[$key]=self::restore(is_array($raw[$key]??null)?$raw[$key]:[],$value);
            elseif (isset($raw[$key]) && is_string($raw[$key])) $defaults[$key]=mb_substr($raw[$key],0,600);
        }
        return $defaults;
    }
    public static function minutes(string $start,string $end): int
    {
        $minute=static fn($t)=>(int)substr($t,0,2)*60+(int)substr($t,3,2);
        return $minute($end)-$minute($start);
    }
    public static function validate(array $input,array $schedule): array
    {
        $data=self::defaults($schedule);
        if (($input['inspection_device_id']??null)!==(string)($schedule['svp_device_id']??'')) throw new RuntimeException('อุปกรณ์ของแผนเปลี่ยนแล้ว กรุณาเปิดแบบฟอร์มใหม่');
        $data['notes']=Portal::text($input,'notes',500,false);
        if (substr_count($data['notes'],"\n")>8 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$data['notes'])) throw new RuntimeException('หมายเหตุรองรับไม่เกิน 9 บรรทัด และไม่รองรับอักขระควบคุม');
        foreach (['device_name'=>150,'serial_number'=>100,'department'=>150,'inspector'=>120,'manager'=>120,'acknowledged_by'=>120,'start_time'=>5,'end_time'=>5] as $key=>$max) {
            $data[$key]=Portal::text($input,$key,$max,in_array($key,['device_name','inspector','start_time','end_time'],true));
            if (isset($input[$key]) && !is_string($input[$key])) throw new RuntimeException('รูปแบบข้อมูลส่วนหัวไม่ถูกต้อง');
            if (preg_match('/[\r\n\x00-\x08\x0B\x0C\x0E-\x1F]/',$data[$key])) throw new RuntimeException('ข้อมูลส่วนหัวต้องเป็นข้อความบรรทัดเดียว');
        }
        foreach (['start_time','end_time'] as $key) if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D',$data[$key])) throw new RuntimeException('กรุณาระบุเวลาเริ่มและสิ้นสุดให้ถูกต้อง');
        if (self::minutes($data['start_time'],$data['end_time'])<0) throw new RuntimeException('เวลาสิ้นสุดต้องไม่น้อยกว่าเวลาเริ่ม (การตรวจภายในวันเดียวกัน)');
        $issue=false; $incomplete=false;
        foreach(self::SECTIONS as $section=>[$title,$start,$end,$fields]) {
            if (!isset($input[$section]) || !is_array($input[$section])) throw new RuntimeException('ไม่พบตาราง '.$title);
            foreach($input[$section] as $r=>$raw) if (!ctype_digit((string)$r) || $r<$start || $r>$end || !is_array($raw)) throw new RuntimeException('รูปแบบแถวไม่ถูกต้อง');
            for($r=$start;$r<=$end;$r++) {
                $raw=$input[$section][$r]??[]; $entry=[];
                foreach($fields as $key=>$label) {
                    $entry[$key]=Portal::text($raw,$key,$key==='notes'?300:160,false);
                    if (isset($raw[$key]) && !is_string($raw[$key])) throw new RuntimeException('รูปแบบช่อง '.$label.' ไม่ถูกต้อง');
                    if (substr_count($entry[$key],"\n")>8 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$entry[$key])) throw new RuntimeException('ข้อความในตารางรองรับไม่เกิน 9 บรรทัด และไม่รองรับอักขระควบคุม');
                    $options=self::options($section,$key);
                    if ($options!==null && $entry[$key]!=='' && !isset($options[$entry[$key]])) throw new RuntimeException('ค่า '.$label.' ไม่ถูกต้อง');
                }
                // Fixed inspection headings cannot be removed to bypass completion checks.
                if ($section==='checks' && isset(self::MAIN[$r])) {
                    $entry['topic']=self::MAIN[$r][0];
                    if (self::MAIN[$r][1]!=='') $entry['method']=self::MAIN[$r][1];
                }
                $active=implode('',$entry)!=='';
                if ($active) {
                    if ($entry['topic']==='') throw new RuntimeException($title.' แถว '.$r.': กรุณาระบุหัวข้อ');
                    if ($entry['status']==='') $incomplete=true;
                    if ($entry['result']==='' && !($section==='checks' && $entry['status']==='absent')) $incomplete=true;
                    if ($entry['result']==='abnormal') $issue=true;
                    if ($section==='work' && $entry['status']==='pending') $incomplete=true;
                    if ($section==='checks' && $entry['status']==='absent' && $entry['result']!=='') throw new RuntimeException('รายการที่ไม่มีอุปกรณ์ ไม่ต้องเลือกผลปกติ/ผิดปกติ');
                }
                $data[$section][$r]=$entry;
            }
        }
        $status=$input['result_status']??'';
        if ($issue && $status!=='issue_found') throw new RuntimeException('มีรายการผิดปกติ กรุณาเลือกสรุปผลว่า พบปัญหา');
        if ($incomplete && $status==='completed') throw new RuntimeException('ยังตรวจไม่ครบ กรุณากรอกสถานะและผลตรวจให้ครบ หรือเลือก ทำได้บางส่วน');
        return $data;
    }
}
