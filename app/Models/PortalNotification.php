<?php
class PortalNotification extends Model
{
    private function scope(bool $ticketsOnly): string
    {
        return 'user_id=:user' . ($ticketsOnly ? " AND EXISTS (SELECT 1 FROM work_items w WHERE w.id=portal_notifications.work_id AND w.kind='ticket' AND (w.requester_id=portal_notifications.user_id OR w.assignee_id=portal_notifications.user_id))" : '');
    }
    public function listing(int $user, int $page = 1, bool $ticketsOnly = false): array
    {
        $params = ['user' => $user];
        $where = $this->scope($ticketsOnly);
        $paging = Portal::page((int)$this->query('SELECT COUNT(*) FROM portal_notifications WHERE ' . $where, $params)->fetchColumn(), $page);
        return $paging + ['rows' => $this->query('SELECT * FROM portal_notifications WHERE ' . $where . ' ORDER BY id DESC LIMIT 15 OFFSET ' . $paging['offset'], $params)->fetchAll()];
    }
    public function unread(int $user, bool $ticketsOnly = false): int
    {
        return (int)$this->query('SELECT COUNT(*) FROM portal_notifications WHERE ' . $this->scope($ticketsOnly) . ' AND read_at IS NULL', ['user' => $user])->fetchColumn();
    }
    public function markRead(int $user, bool $ticketsOnly = false): void
    {
        $this->query('UPDATE portal_notifications SET read_at=:now WHERE ' . $this->scope($ticketsOnly) . ' AND read_at IS NULL', ['now' => Portal::now(), 'user' => $user]);
    }
}
