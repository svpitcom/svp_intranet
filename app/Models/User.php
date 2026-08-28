<?php
class User extends Model
{
    protected string $table = 'users';

    public function findByUsername(string $username): ?array
    {
        $row = $this->query(
            "SELECT * FROM users WHERE username = :username LIMIT 1",
            ['username' => $username]
        )->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        return $this->find($id, 'svp_user_id');
    }

    public function allWithDepartmentAndPosition(): array
    {
        $sql = "SELECT u.svp_user_id, u.username, u.first_name, u.last_name, u.email,
                       u.user_role, u.is_active,
                       d.svp_department_name, p.position_name
                FROM users u
                LEFT JOIN department d ON d.svp_department_id = u.svp_department_id
                LEFT JOIN position p ON p.svp_position_id = u.svp_position_id
                ORDER BY u.first_name";
        return $this->query($sql)->fetchAll();
    }
}
