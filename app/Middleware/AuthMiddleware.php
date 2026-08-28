<?php
class AuthMiddleware
{
    public function handle(array $args = []): void
    {
        if (!Session::has('user')) {
            Session::flash('error', 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
            header('Location: ' . APP_URL . '/login');
            exit;
        }
    }
}
