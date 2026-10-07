<?php
class ControlledDocument extends Model
{
    protected string $table = 'controlled_documents';
    public const STATUSES = ['draft'=>'ฉบับร่าง', 'active'=>'ใช้งาน', 'obsolete'=>'ยกเลิกใช้'];

    public function listing(string $q, string $status, int $page): array
    {
        $where = ' WHERE 1=1'; $params=[];
        if ($q !== '') {
            $where .= " AND (c.code LIKE :q1 OR c.title LIKE :q2)";
            $params = ['q1'=>'%'.$q.'%', 'q2'=>'%'.$q.'%'];
        }
        if (isset(self::STATUSES[$status])) { $where .= ' AND c.status=:status'; $params['status']=$status; }
        $total=(int)$this->query('SELECT COUNT(*) FROM controlled_documents c'.$where,$params)->fetchColumn();
        $pages=max(1,(int)ceil($total/25)); $page=max(1,min($pages,$page)); $offset=($page-1)*25;
        $rows=$this->query('SELECT c.*, d.svp_department_name FROM controlled_documents c LEFT JOIN department d ON d.svp_department_id=c.department_id'.$where.' ORDER BY c.updated_at DESC, c.id DESC LIMIT 25 OFFSET '.$offset,$params)->fetchAll();
        $counts=array_fill_keys(array_keys(self::STATUSES),0);
        foreach ($this->query('SELECT status, COUNT(*) AS amount FROM controlled_documents GROUP BY status') as $r) $counts[$r['status']]=(int)$r['amount'];
        return compact('rows','total','pages','page','counts');
    }

    public function history(int $id): array
    {
        return $this->query('SELECT h.*, u.first_name, u.last_name FROM controlled_document_history h LEFT JOIN users u ON u.svp_user_id=h.changed_by WHERE document_id=:id ORDER BY h.id DESC',['id'=>$id])->fetchAll();
    }

    /** Current record and revision snapshot are committed together; version prevents lost updates. */
    public function saveDocument(?int $id, array $data, int $version, int $actor): int
    {
        $now=(new DateTimeImmutable('now',new DateTimeZone('Asia/Bangkok')))->format('Y-m-d H:i:s');
        try {
            $this->db->beginTransaction();
            if ($id !== null) {
                $old=$this->query('SELECT * FROM controlled_documents WHERE id=:id FOR UPDATE',['id'=>$id])->fetch();
                if (!$old) throw new RuntimeException('ไม่พบเอกสารนี้');
                if ((int)$old['version'] !== $version) throw new RuntimeException('มีผู้แก้ไขเอกสารนี้แล้ว กรุณาเปิดหน้าใหม่ก่อนบันทึก');
            }
            if ($this->query('SELECT id FROM controlled_documents WHERE code=:code AND id<>:id',['code'=>$data['code'],'id'=>$id??0])->fetch()) throw new RuntimeException('รหัสเอกสารนี้มีอยู่แล้ว กรุณาแก้ไขรายการเดิม');
            $data += ['updated_by'=>$actor,'updated_at'=>$now,'version'=>$id===null?1:$version+1];
            if ($id===null) { $data['created_at']=$now; $id=$this->insert($data); }
            else $this->update($id,$data);
            $this->query('INSERT INTO controlled_document_history (document_id,snapshot,changed_by,changed_at) VALUES (:id,:snapshot,:actor,:at)',[
                'id'=>$id,'snapshot'=>json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'actor'=>$actor,'at'=>$now,
            ]);
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($e instanceof PDOException) throw new RuntimeException('บันทึกไม่สำเร็จ กรุณาตรวจรหัสเอกสารซ้ำหรือข้อมูลที่ยาวเกินกำหนด');
            throw $e;
        }
    }
}
