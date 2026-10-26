<?php
class NotificationController extends Controller
{
    public function index(): void
    {
        $user = $this->currentUser();
        $this->view('notifications/index', (new PortalNotification())->listing((int)$user['svp_user_id'], (int)$this->input('page', '1'), DccWorkspace::restricted($user)));
    }
    public function read(): void
    {
        $user = $this->currentUser();
        (new PortalNotification())->markRead((int)$user['svp_user_id'], DccWorkspace::restricted($user));
        $this->redirect('/notifications');
    }
}
