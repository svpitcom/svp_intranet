<?php
final class MachineRepairForm extends Model
{
    public const CODE = 'MN-F-001-02';
    public const PRIORITIES = ['P1'=>'P1', 'P2'=>'P2', 'P3'=>'P3'];
    public const PRIORITY_MAP = ['P1'=>'urgent', 'P2'=>'high', 'P3'=>'normal'];
    public const REQUEST_FIELDS = ['machine_name','report_date','report_time','department_name','access_date','access_time','request_priority'];
    public const REPAIR_FIELDS = ['receiver_name','received_date','repair_priority','repair_method','abnormality','abnormality_other','repairer_name','repair_date','repair_end_date','start_time','end_time','cause','repair_detail','prevention'];
    public const ACCEPTANCE_FIELDS = ['acceptance_result','inspection_date','suggestions'];
    public static function displayInput(array $input): array
    {
        $safe=[];
        foreach (array_merge(self::REPAIR_FIELDS,self::ACCEPTANCE_FIELDS) as $key) if (isset($input[$key]) && is_string($input[$key])) $safe[$key]=mb_substr($input[$key],0,5000);
        if (is_array($input['technicians']??null)) $safe['technicians']=array_map(static fn($v)=>is_string($v)?mb_substr($v,0,200):'',array_slice(array_values($input['technicians']),0,6));
        if (is_array($input['parts']??null)) foreach (array_slice(array_values($input['parts']),0,7) as $row) {
            $part=[]; foreach (['code','description','quantity','unit','note'] as $key) $part[$key]=is_array($row) && is_string($row[$key]??null)?mb_substr($row[$key],0,500):'';
            $safe['parts'][]=$part;
        }
        return $safe;
    }
    private static function choice(array $data, string $key, array $choices, bool $required=true): string
    {
        $value=Portal::text($data,$key,80,$required);
        if ($value!=='' && !array_key_exists($value,$choices)) throw new RuntimeException('ตัวเลือกไม่ถูกต้อง: '.$key);
        return $value;
    }
    private static function date(array $data, string $key, bool $required): string
    {
        $value=Portal::text($data,$key,10,$required);
        if ($value!=='' && !Validation::date($value)) throw new RuntimeException('วันที่ไม่ถูกต้อง: '.$key);
        return $value;
    }
    private static function time(array $data, string $key, bool $required): string
    {
        $value=Portal::text($data,$key,5,$required);
        if ($value!=='' && !preg_match('/\A(?:[01][0-9]|2[0-3]):[0-5][0-9]\z/',$value)) throw new RuntimeException('เวลาไม่ถูกต้อง: '.$key);
        return $value;
    }
    public static function requestData(array $input,array $user): array
    {
        $data=[];
        foreach (['machine_name','department_name'] as $key) $data[$key]=Portal::text($input,$key,200);
        $data['report_date']=self::date($input,'report_date',true);
        $data['report_time']=self::time($input,'report_time',true);
        if ($data['report_date'].' '.$data['report_time']>substr(Portal::now(),0,16)) throw new RuntimeException('วันเวลาแจ้งซ่อมต้องไม่อยู่ในอนาคต');
        $data['access_date']=self::date($input,'access_date',false);
        $data['access_time']=self::time($input,'access_time',false);
        if (($data['access_date']==='')!==($data['access_time']==='')) throw new RuntimeException('กรอกวันที่และเวลาที่สามารถเข้าแก้ไขให้ครบทั้งคู่');
        if ($data['access_date']!=='' && $data['access_date'].' '.$data['access_time']<$data['report_date'].' '.$data['report_time']) throw new RuntimeException('เวลาเข้าแก้ไขต้องไม่ก่อนเวลาแจ้งซ่อม');
        $data['request_priority']=self::choice($input,'request_priority',self::PRIORITIES);
        $data['requester_name']=trim(($user['first_name']??'').' '.($user['last_name']??''));
        return $data;
    }
    public static function repairData(array $input,bool $complete,array $user): array
    {
        $data=[];
        foreach (['receiver_name','repairer_name'] as $key) $data[$key]=Portal::text($input,$key,200,$complete);
        foreach (['cause','repair_detail','prevention'] as $key) $data[$key]=Portal::text($input,$key,5000,$complete);
        $data['abnormality_other']=Portal::text($input,'abnormality_other',200,false);
        $data['repair_priority']=self::choice($input,'repair_priority',self::PRIORITIES,$complete);
        $data['repair_method']=self::choice($input,'repair_method',['internal'=>'ซ่อมภายใน','external'=>'ซ่อมนอก'],$complete);
        $data['abnormality']=self::choice($input,'abnormality',['EE'=>'ทางไฟฟ้า','ME'=>'ทางเครื่องจักร','other'=>'อื่น ๆ'],$complete);
        if ($complete && $data['abnormality']==='other' && $data['abnormality_other']==='') throw new RuntimeException('กรุณาระบุความผิดปกติอื่น ๆ');
        foreach (['received_date','repair_date','repair_end_date'] as $key) $data[$key]=self::date($input,$key,$complete);
        foreach (['start_time','end_time'] as $key) $data[$key]=self::time($input,$key,$complete);
        $data['duration_minutes']=null;
        if ($data['repair_date'] && $data['repair_end_date'] && $data['start_time'] && $data['end_time']) {
            $zone=new DateTimeZone('Asia/Bangkok');
            $from=new DateTimeImmutable($data['repair_date'].' '.$data['start_time'],$zone);
            $to=new DateTimeImmutable($data['repair_end_date'].' '.$data['end_time'],$zone);
            if ($to<$from) throw new RuntimeException('เวลาแก้ไขเสร็จต้องไม่ก่อนเวลาเริ่ม');
            if ($to>new DateTimeImmutable('now',$zone)) throw new RuntimeException('เวลาแก้ไขเสร็จต้องไม่อยู่ในอนาคต');
            $data['duration_minutes']=(int)(($to->getTimestamp()-$from->getTimestamp())/60);
        }
        if ($data['received_date'] && $data['repair_date'] && $data['received_date']>$data['repair_date']) throw new RuntimeException('วันที่รับแจ้งต้องไม่หลังวันที่เริ่มซ่อม');
        $technicians=$input['technicians']??[];
        if (!is_array($technicians) || count($technicians)>6) throw new RuntimeException('รายชื่อช่างได้สูงสุด 6 คน');
        $data['technicians']=[];
        foreach ($technicians as $name) { $name=Portal::text(['name'=>$name],'name',200,false); if ($name!=='') $data['technicians'][]=$name; }
        $parts=$input['parts']??[];
        if (!is_array($parts) || count($parts)>7) throw new RuntimeException('รายการอะไหล่ได้สูงสุด 7 รายการ');
        $data['parts']=[];
        foreach ($parts as $part) {
            if (!is_array($part)) throw new RuntimeException('ข้อมูลอะไหล่ไม่ถูกต้อง');
            $row=[]; foreach (['code'=>100,'description'=>500,'quantity'=>20,'unit'=>80,'note'=>500] as $key=>$max) $row[$key]=Portal::text($part,$key,$max,false);
            if (implode('',$row)==='') continue;
            if (!$row['description'] || !$row['unit'] || !preg_match('/\A(?:0|[1-9][0-9]{0,8})(?:\.[0-9]{1,3})?\z/',$row['quantity']) || (float)$row['quantity']<=0) throw new RuntimeException('อะไหล่ต้องมีรายละเอียด จำนวนมากกว่า 0 และหน่วย');
            $data['parts'][]=$row;
        }
        $data['recorded_by']=(int)$user['svp_user_id']; $data['recorded_at']=Portal::now();
        if (strlen(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))>60000) throw new RuntimeException('รายละเอียดผลซ่อมและอะไหล่รวมกันยาวเกินกำหนด กรุณาย่อรายละเอียดก่อนบันทึก');
        return $data;
    }
    public static function acceptanceData(array $input,string $action,array $user): array
    {
        $result=self::choice($input,'acceptance_result',['normal'=>'พร้อมใช้งานปกติ','temporary'=>'ชั่วคราว / แก้ไขอีกครั้ง']);
        if (($action==='close' && $result!=='normal') || ($action==='reopen' && $result!=='temporary')) throw new RuntimeException('ผลตรวจรับต้องตรงกับปุ่มยืนยันปิดเคสหรือส่งกลับแก้ไข');
        $date=self::date($input,'inspection_date',true);
        if ($date>Portal::today()) throw new RuntimeException('วันที่ตรวจรับต้องไม่อยู่ในอนาคต');
        return ['acceptance_result'=>$result,'inspection_date'=>$date,'suggestions'=>Portal::text($input,'suggestions',5000,$action==='reopen'),'inspector_name'=>trim(($user['first_name']??'').' '.($user['last_name']??'')),'inspector_id'=>(int)$user['svp_user_id'],'recorded_at'=>Portal::now()];
    }
    public function load(int $id): ?array
    {
        $row=$this->query('SELECT * FROM work_machine_forms WHERE work_id=?',[$id])->fetch();
        if (!$row) return null;
        foreach (['request_data','repair_data','acceptance_data'] as $key) $row[$key]=$row[$key]?json_decode($row[$key],true,512,JSON_THROW_ON_ERROR):[];
        return $row;
    }
    public function createFor(int $id,array $data): void
    {
        $this->query('INSERT INTO work_machine_forms(work_id,request_data) VALUES(?,?)',[$id,json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    }
    public function saveSection(int $id,string $section,array $data): void
    {
        if (!in_array($section,['repair_data','acceptance_data'],true)) throw new LogicException('Invalid section');
        $this->query('UPDATE work_machine_forms SET '.$section.'=? WHERE work_id=?',[json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$id]);
    }
}
