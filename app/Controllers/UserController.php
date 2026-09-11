<?php
class UserController extends Controller
{
    public function index(): void
    {
        $perPage = 10;
        $page = max(1, (int) $this->input('page', 1));
        $sort = $this->input('sort', 'svp_user_id');
        $dir  = $this->input('dir', 'asc');
        $search = trim($this->input('search', ''));

        $this->view('users/index', $this->buildUserListData($page, $perPage, $sort, $dir, $search));
    }

    /** Endpoint สำหรับ AJAX — คืนแค่ HTML fragment ของตาราง ไม่ใช่หน้าเต็ม */
    public function search(): void
    {
        $perPage = 10;
        $page = max(1, (int) $this->input('page', 1));
        $sort = $this->input('sort', 'svp_user_id');
        $dir  = $this->input('dir', 'asc');
        $search = trim($this->input('search', ''));

        $data = $this->buildUserListData($page, $perPage, $sort, $dir, $search);
        extract($data);

        // เรนเดอร์แค่ partial ตรงๆ ไม่ผ่าน layout
        require BASE_PATH . '/app/Views/users/_table.php';

        // แนบ total count ไว้ใน HTML comment ให้ JS ฝั่ง client อ่านไปอัปเดตหัวข้อได้
        echo '<!-- data-total-count="' . $totalUsers . '" -->';
    }

    private function buildUserListData(int $page, int $perPage, string $sort, string $dir, string $search): array
    {
        $userModel = new User();
        $totalUsers = $userModel->countAll($search);
        $totalPages = max(1, (int) ceil($totalUsers / $perPage));
        $page = min($page, $totalPages);

        $users = $userModel->paginateWithRelations($page, $perPage, $sort, $dir, $search);

        return [
            'users'       => $users,
            'totalUsers'  => $totalUsers,
            'currentPage' => $page,
            'perPage'     => $perPage,
            'totalPages'  => $totalPages,
            'sort'        => $sort,
            'dir'         => $dir,
            'search'      => $search,
            'currentUser' => $this->currentUser(),
        ];
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
