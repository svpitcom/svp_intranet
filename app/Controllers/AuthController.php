<?php
class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Session::has('user')) $this->redirect('/');
        $this->view('auth/login', [], 'auth');
    }

    public function login(): void
    {
        $username = $this->input('username');
        $password = $this->input('password');

        if (!$username || !$password) {
            Session::flash('error', 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
            $this->redirect('/login');
        }

        $user = (new User())->findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            Session::flash('error', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
            $this->redirect('/login');
        }

        if (!$user['is_active']) {
            Session::flash('error', 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ');
            $this->redirect('/login');
        }

        unset($user['password']);
        Session::set('user', $user);
        $this->redirect('/');
    }

    public function logout(): void
    {
        Session::destroy();
        $this->redirect('/login');
    }
}
