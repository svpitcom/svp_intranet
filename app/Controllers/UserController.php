<?php
class UserController extends Controller
{
    public function index(): void
    {
        $users = (new User())->allWithDepartmentAndPosition();
        $this->view('users/index', ['users' => $users]);
    }

    public function create(): void
    {
        $this->view('users/form', [
            'user' => null,
            'positions' => (new Position())->all(),
            'departments' => (new Department())->all(),
        ]);
    }

    public function store(): void
    {
        $errors = $this->validate();
        if ($errors) {
            Session::flash('errors', implode(' / ', $errors));
            $this->redirect('/users/create');
        }

        (new User())->insert([
            'username'          => $this->input('username'),
            'password'          => password_hash($this->input('password'), PASSWORD_BCRYPT),
            'first_name'        => $this->input('first_name'),
            'last_name'         => $this->input('last_name'),
            'email'             => $this->input('email'),
            'svp_position_id'   => $this->input('svp_position_id') ?: null,
            'svp_department_id' => $this->input('svp_department_id') ?: null,
            'user_role'         => $this->input('user_role', 'user'),
            'is_active'         => 1,
        ]);

        Session::flash('success', 'เพิ่มผู้ใช้เรียบร้อยแล้ว');
        $this->redirect('/users');
    }

    public function edit(string $id): void
    {
        $user = (new User())->findById((int) $id);
        if (!$user) {
            Session::flash('error', 'ไม่พบผู้ใช้ที่ต้องการแก้ไข');
            $this->redirect('/users');
        }

        $this->view('users/form', [
            'user' => $user,
            'positions' => (new Position())->all(),
            'departments' => (new Department())->all(),
        ]);
    }

    public function update(string $id): void
    {
        $id = (int) $id;
        $errors = $this->validate(isUpdate: true);
        if ($errors) {
            Session::flash('errors', implode(' / ', $errors));
            $this->redirect("/users/{$id}/edit");
        }

        $data = [
            'username'          => $this->input('username'),
            'first_name'        => $this->input('first_name'),
            'last_name'         => $this->input('last_name'),
            'email'             => $this->input('email'),
            'svp_position_id'   => $this->input('svp_position_id') ?: null,
            'svp_department_id' => $this->input('svp_department_id') ?: null,
            'user_role'         => $this->input('user_role', 'user'),
            'is_active'         => $this->input('is_active') ? 1 : 0,
        ];

        $newPassword = $this->input('password');
        if ($newPassword) $data['password'] = password_hash($newPassword, PASSWORD_BCRYPT);

        (new User())->update($id, $data, 'svp_user_id');

        Session::flash('success', 'บันทึกการแก้ไขเรียบร้อยแล้ว');
        $this->redirect('/users');
    }

    public function destroy(string $id): void
    {
        $id = (int) $id;
        $currentUser = $this->currentUser();

        if ($currentUser['svp_user_id'] == $id) {
            Session::flash('error', 'ไม่สามารถลบบัญชีของตัวเองได้');
            $this->redirect('/users');
        }

        (new User())->delete($id, 'svp_user_id');
        Session::flash('success', 'ลบผู้ใช้เรียบร้อยแล้ว');
        $this->redirect('/users');
    }

    private function validate(bool $isUpdate = false): array
    {
        $errors = [];
        if (!$this->input('username')) $errors[] = 'กรุณากรอกชื่อผู้ใช้';
        if (!$this->input('first_name')) $errors[] = 'กรุณากรอกชื่อ';
        if (!$this->input('last_name')) $errors[] = 'กรุณากรอกนามสกุล';

        $email = $this->input('email');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'กรุณากรอกอีเมลให้ถูกต้อง';
        if (!$isUpdate && !$this->input('password')) $errors[] = 'กรุณากรอกรหัสผ่าน';

        return $errors;
    }
}
