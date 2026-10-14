<?php
class Announcement extends Model
{
    protected string $table='announcements';
    public const STATUSES=['draft'=>'ฉบับร่าง','published'=>'เผยแพร่','archived'=>'เก็บเข้าคลัง'];
    private function visibility(array $user): array
    {
        return ["a.status='published' AND a.publish_date<=:today AND (a.expires_date IS NULL OR a.expires_date>=:today2) AND (a.department_id IS NULL OR a.department_id=:dept)",['today'=>Portal::today(),'today2'=>Portal::today(),'dept'=>$user['svp_department_id']??0]];
    }
    public function listing(array $user, string $q='', int $page=1, bool $manage=false): array
    {
        [$where,$params]=$manage&&Portal::manager($user)?['1=1',[]]:$this->visibility($user);
        if($q!=='') { $where.=' AND (a.title LIKE :q OR a.body LIKE :q2)'; $params+=['q'=>'%'.$q.'%','q2'=>'%'.$q.'%']; }
        $paging=Portal::page((int)$this->query('SELECT COUNT(*) FROM announcements a WHERE '.$where,$params)->fetchColumn(),$page);
        $rows=$this->query('SELECT a.*,d.svp_department_name,(SELECT r.read_at FROM announcement_reads r WHERE r.announcement_id=a.id AND r.user_id=:reader) AS read_at FROM announcements a LEFT JOIN department d ON d.svp_department_id=a.department_id WHERE '.$where.' ORDER BY a.pinned DESC,a.publish_date DESC,a.id DESC LIMIT 15 OFFSET '.$paging['offset'],$params+['reader'=>$user['svp_user_id']])->fetchAll();
        return $paging+['rows'=>$rows];
    }
    public function detail(int $id, array $user): array
    {
        [$where,$params]=Portal::manager($user)?['1=1',[]]:$this->visibility($user);
        $row=$this->query('SELECT a.*,d.svp_department_name FROM announcements a LEFT JOIN department d ON d.svp_department_id=a.department_id WHERE a.id=:id AND '.$where,$params+['id'=>$id])->fetch();
        if(!$row)throw new RuntimeException('ไม่พบประกาศหรือไม่มีสิทธิ์เข้าถึง');
        return $row;
    }
    public function saveAnnouncement(?int $id, array $data, array $user, int $version): int
    {
        if(!Portal::manager($user))throw new RuntimeException('ไม่มีสิทธิ์จัดการประกาศ');
        $payload=['title'=>Portal::text($data,'title',200),'body'=>Portal::text($data,'body',20000),'department_id'=>Portal::id($data['department_id']??null),'status'=>$data['status']??'draft','publish_date'=>$data['publish_date']??'','expires_date'=>($data['expires_date']??'')?:null,'pinned'=>!empty($data['pinned'])?1:0,'updated_at'=>Portal::now()];
        if(!is_string($payload['status'])||!isset(self::STATUSES[$payload['status']]))throw new RuntimeException('สถานะประกาศไม่ถูกต้อง');
        if(!Validation::date($payload['publish_date'])||($payload['expires_date']!==null&&(!Validation::date($payload['expires_date'])||$payload['expires_date']<$payload['publish_date'])))throw new RuntimeException('ช่วงวันที่เผยแพร่ไม่ถูกต้อง');
        if($payload['department_id']&&!$this->query('SELECT svp_department_id FROM department WHERE svp_department_id=:id',['id'=>$payload['department_id']])->fetch())throw new RuntimeException('ไม่พบแผนก');
        $this->db->beginTransaction();
        try {
            if($id) {
                $old=$this->query('SELECT * FROM announcements WHERE id=:id FOR UPDATE',['id'=>$id])->fetch();
                if(!$old||(int)$old['version']!==$version)throw new RuntimeException('ประกาศถูกแก้ไขแล้ว กรุณาโหลดหน้าใหม่');
                $payload['version']=$version+1; $this->update($id,$payload);
                // Acknowledgements apply to the exact published content and audience.
                if($old['title']!==$payload['title']||$old['body']!==$payload['body']||(string)$old['department_id']!==(string)$payload['department_id'])$this->query('DELETE FROM announcement_reads WHERE announcement_id=:id',['id'=>$id]);
            } else { $id=$this->insert($payload+['author_id'=>$user['svp_user_id'],'created_at'=>Portal::now()]); }
            $this->db->commit(); return $id;
        } catch(Throwable $e) { if($this->db->inTransaction())$this->db->rollBack(); throw $e; }
    }
    public function acknowledge(int $id, array $user, int $version): void
    {
        $this->db->beginTransaction();
        try {
            $this->query('SELECT id FROM announcements WHERE id=:id FOR UPDATE',['id'=>$id])->fetch();
            $row=$this->detail($id,$user);
            if((int)$row['version']!==$version)throw new RuntimeException('ประกาศเปลี่ยนแล้ว กรุณาอ่านฉบับล่าสุด');
            if($row['status']!=='published'||$row['publish_date']>Portal::today()||($row['expires_date']&&$row['expires_date']<Portal::today()))throw new RuntimeException('ประกาศนี้ยังไม่เผยแพร่หรือหมดอายุแล้ว');
            $params=['id'=>$id,'user'=>$user['svp_user_id']];
            if(!$this->query('SELECT user_id FROM announcement_reads WHERE announcement_id=:id AND user_id=:user',$params)->fetch())$this->query('INSERT INTO announcement_reads(announcement_id,user_id,read_at) VALUES (:id,:user,:now)',$params+['now'=>Portal::now()]);
            $this->db->commit();
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    public function readers(int $id): array
    {
        return $this->query('SELECT r.read_at,u.first_name,u.last_name FROM announcement_reads r LEFT JOIN users u ON u.svp_user_id=r.user_id WHERE r.announcement_id=:id ORDER BY r.read_at DESC',['id'=>$id])->fetchAll();
    }
}
