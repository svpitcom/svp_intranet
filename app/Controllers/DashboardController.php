<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        if (DccWorkspace::restricted($this->currentUser())) {
            $documents = (new ControlledDocument())->listing('', '', 1);
            $this->view('dashboard/dcc', ['user' => $this->currentUser(), 'counts' => $documents['counts'], 'total' => $documents['total']]);
            return;
        }
        $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Bangkok')))->format('Y-m-d');
        $this->view('dashboard/index', (new Dashboard())->overview($today) + [
            'today' => $today,
            'user' => $this->currentUser(),
            'workStats' => (new WorkItem())->overview($this->currentUser()),
            'announcements' => array_slice((new Announcement())->listing($this->currentUser())['rows'], 0, 3),
        ]);
    }
}
