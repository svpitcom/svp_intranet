<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Bangkok')))->format('Y-m-d');
        $this->view('dashboard/index', (new Dashboard())->overview($today) + [
            'today' => $today, 'user' => $this->currentUser(),
        ]);
    }
}
