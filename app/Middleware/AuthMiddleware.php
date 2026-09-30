<?php
class AuthMiddleware
{
    public function handle(array $args = []): void
    {
        if (Session::has('user')) {
            $user = (new User())->findById((int) Session::get('user')['svp_user_id']);
            if (!$user || !$user['is_active']) {
                Session::remove('user');
            } else {
                unset($user['password']);
                Session::set('user', $user);
            }
        }
        if (!Session::has('user')) {
            Session::flash('error', 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
            header('Location: ' . APP_URL . '/login');
            exit;
        }
    }
}
