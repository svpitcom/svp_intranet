<?php
class NotificationController extends Controller
{
    public function index(): void { $this->view('notifications/index',(new PortalNotification())->listing((int)$this->currentUser()['svp_user_id'],(int)$this->input('page','1'))); }
    public function read(): void { (new PortalNotification())->markRead((int)$this->currentUser()['svp_user_id']); $this->redirect('/notifications'); }
}
