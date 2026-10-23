<?php
/** Annual MR-F-027-01 document, independent of monthly calculated score definitions. */
class KpiForm extends Model
{
    protected string $table='kpi_forms';
    public const FIELDS=['owner'=>'ผู้รับผิดชอบ','effective_date'=>'Effective date','prepared_by'=>'Prepared by','approved_by'=>'Approved by','target'=>'Target','measurement'=>'วิธีวัดผล','frequency'=>'ความถี่','action_taken'=>'Action taken','ytd'=>'YTD'];
    public static function clean(array $data): array
    {
        $out=['title'=>Portal::text($data,'title',200)];
        $year=filter_var($data['year']??'',FILTER_VALIDATE_INT,['options'=>['min_range'=>2000,'max_range'=>2099]]);
        if (!$year) throw new RuntimeException('ปีต้องเป็น ค.ศ. 2000–2099');
        $out['year']=$year;
        foreach (self::FIELDS as $key=>$label) $out[$key]=Portal::text($data,$key,$key==='action_taken'?2000:500,false);
        if ($out['effective_date']!=='') {
            $date=DateTimeImmutable::createFromFormat('!Y-m-d',$out['effective_date']);
            if (!$date || $date->format('Y-m-d')!==$out['effective_date']) throw new RuntimeException('Effective date ไม่ถูกต้อง');
        }
        foreach (['qms','ems'] as $key) $out[$key]=($data[$key]??'')==='1';
        foreach (['activities','results'] as $key) if (isset($data[$key]) && !is_array($data[$key])) throw new RuntimeException('โครงสร้างตารางไม่ถูกต้อง');
        if (count($data['activities']??[])>11 || count($data['results']??[])>12) throw new RuntimeException('จำนวนแถวเกินแบบฟอร์ม');
        for ($i=0;$i<11;$i++) {
            $row=$data['activities'][$i]??[];
            if (!is_array($row)) throw new RuntimeException('กิจกรรมไม่ถูกต้อง');
            $a=[]; foreach (['activity','owner','resources'] as $key) $a[$key]=Portal::text($row,$key,300,false);
            $months=$row['months']??[];
            if (!is_array($months)) throw new RuntimeException('เดือนแผนงานไม่ถูกต้อง');
            foreach ($months as $month) if (!is_scalar($month) || !preg_match('/^(?:[1-9]|1[0-2])$/D',(string)$month)) throw new RuntimeException('เดือนแผนงานไม่ถูกต้อง');
            $a['months']=array_values(array_unique(array_map('intval',$months))); sort($a['months']);
            if ($a['activity']==='' && ($a['owner']!=='' || $a['resources']!=='' || $a['months'])) throw new RuntimeException('กรุณาระบุชื่อกิจกรรมในแถวที่ '.($i+1));
            $out['activities'][]=$a;
        }
        for ($i=0;$i<12;$i++) {
            $r=$data['results'][$i]??[];
            if (!is_array($r)) throw new RuntimeException('ผลรายเดือนไม่ถูกต้อง');
            $value=Portal::text($r,'result',30,false);
            if ($value!=='' && !preg_match('/^-?\d{1,10}(\.\d{1,4})?$/D',$value)) throw new RuntimeException('ผลรายเดือนต้องเป็นตัวเลข ทศนิยมไม่เกิน 4 ตำแหน่ง หรือเว้นว่าง');
            $out['results'][]=['result'=>$value];
        }
        return $out;
    }
    public function detail(int $id,array $user): array
    {
        $row=$this->find($id);
        if (!$row || !KpiPolicy::reads($user,$row)) throw new RuntimeException('ไม่พบแบบฟอร์มหรือไม่มีสิทธิ์');
        $row['data']=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR);
        return $row;
    }
    public function listing(array $user): array
    {
        if (!KpiPolicy::allows($user)) throw new RuntimeException('ไม่มีสิทธิ์');
        $where=''; $params=[];
        if (($user['user_role']??'')!=='admin') { $where=' WHERE f.department_id=?'; $params[]=(int)($user['svp_department_id']??0); }
        return $this->query('SELECT f.id,f.title,f.year,f.department_id,f.version,f.updated_at,d.svp_department_name FROM kpi_forms f LEFT JOIN department d ON d.svp_department_id=f.department_id'.$where.' ORDER BY f.year DESC,f.id DESC',$params)->fetchAll();
    }
    public function save(?int $id,array $user,array $input): int
    {
        $data=self::clean($input);
        $dept=Portal::id($input['department_id']??'')??0;
        if (!KpiPolicy::manages($user,$dept)) throw new RuntimeException('เฉพาะผู้จัดการแผนกหรือผู้ดูแลระบบที่แก้ไขแบบฟอร์มได้');
        $department=$this->query('SELECT svp_department_name FROM department WHERE svp_department_id=?',[$dept])->fetchColumn();
        if ($department===false) throw new RuntimeException('ไม่พบแผนก');
        $data['department']=$department;
        $this->db->beginTransaction();
        try {
            $version=1;
            if ($id!==null) {
                $row=$this->query('SELECT * FROM kpi_forms WHERE id=? FOR UPDATE',[$id])->fetch();
                if (!$row || !KpiPolicy::manages($user,(int)$row['department_id'])) throw new RuntimeException('ไม่มีสิทธิ์แก้ไขแบบฟอร์มนี้');
                if ((string)$row['version']!==(string)($input['version']??'')) throw new RuntimeException('แบบฟอร์มถูกแก้ไขแล้ว กรุณาเปิดใหม่ก่อนบันทึก');
                $version=(int)$row['version']+1;
            }
            $json=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            $values=['department_id'=>$dept,'year'=>$data['year'],'title'=>$data['title'],'payload'=>$json,'version'=>$version,'updated_by'=>(int)$user['svp_user_id'],'updated_at'=>Portal::now()];
            if ($id===null) $id=$this->insert($values); else $this->update($id,$values);
            $this->query('INSERT INTO kpi_form_history(form_id,payload,version,actor_id,created_at) VALUES(?,?,?,?,?)',[$id,$json,$version,$user['svp_user_id'],Portal::now()]);
            $this->db->commit(); return $id;
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }
}
