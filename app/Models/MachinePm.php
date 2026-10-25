<?php
final class MachinePm extends Model
{
    protected string $table='machine_pm_schedules';
    public const RESULTS=['completed'=>'ทำครบ / ปกติ','partial'=>'ทำได้บางส่วน','issue_found'=>'พบปัญหา'];
    public const CHECKS=['normal'=>'ปกติ','issue'=>'ผิดปกติ','not_done'=>'ยังไม่ได้ตรวจ'];
    public function __construct(?PDO $db=null) { $this->db=$db??MachineDatabase::connect(); }
    public static function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim',preg_split('/\R/u',$text)?:[]),static fn($line)=>$line!==''));
    }
    public function listing(array $user,string $kind,string $q='',int $page=1): array
    {
        MachineAccess::requireAccess($user);
        $q=mb_substr($q,0,200);
        if ($kind==='records') {
            $from='machine_pm_records p'; $columns='p.*'; $search=['p.machine_code','p.machine_name','p.performed_name']; $order='p.performed_date DESC,p.id DESC';
        } else {
            $from='machine_pm_schedules p JOIN machines m ON m.id=p.machine_id'; $columns='p.*,m.code,m.name,m.is_active AS machine_active'; $search=['m.code','m.name','p.title']; $order='p.is_active DESC,p.next_pm_date,p.id';
        }
        $where=$q!==''?' WHERE '.implode(' OR ',array_map(static fn($col)=>$col.' LIKE ?',$search)):''; $params=$q!==''?array_fill(0,3,'%'.$q.'%'):[];
        $paging=Portal::page((int)$this->query('SELECT COUNT(*) FROM '.$from.$where,$params)->fetchColumn(),$page);
        $rows=$this->query('SELECT '.$columns.' FROM '.$from.$where.' ORDER BY '.$order.' LIMIT '.$paging['size'].' OFFSET '.$paging['offset'],$params)->fetchAll();
        return $paging+['rows'=>$rows];
    }
    public function plan(int $id,array $user): array
    {
        MachineAccess::requireAccess($user);
        $row=$this->query('SELECT p.*,m.code,m.name,m.is_active AS machine_active FROM machine_pm_schedules p JOIN machines m ON m.id=p.machine_id WHERE p.id=?',[$id])->fetch();
        if (!$row) throw new RuntimeException('ไม่พบแผน PM เครื่องจักร');
        return $row;
    }
    public function savePlan(?int $id,array $input,array $user): int
    {
        MachineAccess::requireAccess($user,true);
        $data=['machine_id'=>Portal::id($input['machine_id']??''),'title'=>Portal::text($input,'title',200),
            'next_pm_date'=>Portal::text($input,'next_pm_date',10),'checklist'=>Portal::text($input,'checklist',10000),
            'responsible_id'=>Portal::id($input['responsible_id']??''),'is_active'=>($input['is_active']??'')==='1'?1:0];
        if (!$data['machine_id'] || !Validation::date($data['next_pm_date'])) throw new RuntimeException('กรุณาเลือกเครื่องจักรและวันครบกำหนดที่ถูกต้อง');
        $frequency=filter_var($input['frequency_days']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>36500]]);
        if (!$frequency) throw new RuntimeException('ความถี่ต้องเป็นจำนวนวัน 1–36500');
        $lines=self::lines($data['checklist']);
        if (!$lines || count($lines)>50 || max(array_map('mb_strlen',$lines))>200) throw new RuntimeException('รายการตรวจต้องมี 1–50 ข้อ ข้อละไม่เกิน 200 ตัวอักษร');
        $data['checklist']=implode("\n",$lines); $data['frequency_days']=$frequency;
        $data['responsible_name']=Machine::person($data['responsible_id'],true);
        $this->db->beginTransaction();
        try {
            $machine=$this->query('SELECT * FROM machines WHERE id=? FOR UPDATE',[$data['machine_id']])->fetch();
            if (!$machine || (!$machine['is_active'] && $data['is_active'])) throw new RuntimeException('เครื่องจักรไม่พร้อมใช้งาน กรุณาปิดแผนหรือเปิดใช้งานเครื่องจักรก่อน');
            $old=$id?$this->query('SELECT * FROM machine_pm_schedules WHERE id=? FOR UPDATE',[$id])->fetch():null;
            if ($id && (!$old || (string)$old['version']!==($input['version']??null))) throw new RuntimeException('แผนถูกแก้ไขหรือมีผล PM ใหม่แล้ว กรุณาเปิดหน้าใหม่');
            if ($old && (int)$old['machine_id']!==$data['machine_id']) throw new RuntimeException('เปลี่ยนเครื่องจักรของแผนเดิมไม่ได้ กรุณาสร้างแผนใหม่');
            $data['version']=$old?(int)$old['version']+1:1; $data['updated_at']=Portal::now();
            if ($id) parent::update($id,$data); else $id=$this->insert($data+['created_at'=>Portal::now()]);
            (new Machine($this->db))->event('plan',$id,$user,$data); $this->db->commit(); return $id;
        } catch(Throwable $e) { if($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }
    public function record(int $id,array $input,array $user): int
    {
        MachineAccess::requireAccess($user);
        $date=Portal::text($input,'performed_date',10);
        if (!Validation::date($date) || $date>Portal::today()) throw new RuntimeException('วันที่ทำ PM ต้องเป็นวันที่จริงและไม่อยู่ในอนาคต');
        $status=Portal::text($input,'result_status',20);
        if (!isset(self::RESULTS[$status])) throw new RuntimeException('กรุณาเลือกผลการทำ PM');
        $notes=Portal::text($input,'notes',4000,false);
        $preview=$this->plan($id,$user);
        $this->db->beginTransaction();
        try {
            $machine=$this->query('SELECT * FROM machines WHERE id=? FOR UPDATE',[$preview['machine_id']])->fetch();
            $plan=$this->query('SELECT * FROM machine_pm_schedules WHERE id=? FOR UPDATE',[$id])->fetch();
            if (!$machine || !$machine['is_active'] || !$plan || !$plan['is_active']) throw new RuntimeException('เครื่องจักรหรือแผนปิดใช้งานแล้ว');
            if ((int)$plan['machine_id']!==(int)$machine['id'] || (string)$plan['version']!==($input['version']??null)) throw new RuntimeException('แผนเปลี่ยนแล้วหรือบันทึกผลนี้แล้ว กรุณาเปิดหน้าใหม่');
            $checks=[]; $issue=false; $incomplete=false;
            foreach(self::lines($plan['checklist']) as $i=>$title) {
                $raw=$input['checks'][$i]??[];
                if (!is_array($raw)) throw new RuntimeException('รูปแบบผลตรวจไม่ถูกต้อง');
                $result=Portal::text($raw,'result',20,false); $note=Portal::text($raw,'note',500,false);
                if ($result!=='' && !isset(self::CHECKS[$result])) throw new RuntimeException('ผลตรวจรายข้อไม่ถูกต้อง');
                $issue=$issue || $result==='issue'; $incomplete=$incomplete || in_array($result,['','not_done'],true);
                $checks[]=['title'=>$title,'result'=>$result,'note'=>$note];
            }
            if ($issue && $status!=='issue_found') throw new RuntimeException('มีข้อผิดปกติ กรุณาเลือก พบปัญหา');
            if ($incomplete && $status==='completed') throw new RuntimeException('ยังตรวจไม่ครบ กรุณากรอกผลหรือเลือก ทำได้บางส่วน');
            $snapshot=['machine'=>$machine,'plan'=>$plan,'checks'=>$checks,'notes'=>$notes];
            $this->query('INSERT INTO machine_pm_records(schedule_id,machine_id,machine_code,machine_name,performed_date,performed_by,performed_name,result_status,payload,created_at) VALUES(?,?,?,?,?,?,?,?,?,?)',[$id,$machine['id'],$machine['code'],$machine['name'],$date,$user['svp_user_id'],Machine::actor($user),$status,json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),Portal::now()]);
            $recordId=(int)$this->db->lastInsertId();
            $update=['version'=>(int)$plan['version']+1,'updated_at'=>Portal::now()];
            if ($status==='completed' && (empty($plan['last_pm_date']) || $date>=$plan['last_pm_date'])) {
                $update['last_pm_date']=$date; $update['next_pm_date']=(new DateTimeImmutable($date))->modify('+'.$plan['frequency_days'].' days')->format('Y-m-d');
            }
            parent::update($id,$update);
            (new Machine($this->db))->event('record',$recordId,$user,['schedule_id'=>$id,'performed_date'=>$date,'result_status'=>$status]);
            $this->db->commit(); return $recordId;
        } catch(Throwable $e) { if($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }
    public function report(int $id,array $user): array
    {
        MachineAccess::requireAccess($user);
        $row=$this->query('SELECT * FROM machine_pm_records WHERE id=?',[$id])->fetch();
        if (!$row) throw new RuntimeException('ไม่พบประวัติ PM เครื่องจักร');
        $row['data']=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR); return $row;
    }
}
