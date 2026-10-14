<?php
class PortalNotification extends Model
{
    public function listing(int $user, int $page=1): array
    {
        $params=['user'=>$user];
        $paging=Portal::page((int)$this->query('SELECT COUNT(*) FROM portal_notifications WHERE user_id=:user',$params)->fetchColumn(),$page);
        return $paging+['rows'=>$this->query('SELECT * FROM portal_notifications WHERE user_id=:user ORDER BY id DESC LIMIT 15 OFFSET '.$paging['offset'],$params)->fetchAll()];
    }
    public function markRead(int $user): void
    {
        $this->query('UPDATE portal_notifications SET read_at=:now WHERE user_id=:user AND read_at IS NULL',['now'=>Portal::now(),'user'=>$user]);
    }
}
