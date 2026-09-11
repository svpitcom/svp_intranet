<?php
class User extends Model
{
    protected string $table = 'users';

    private const SORTABLE_COLUMNS = [
        'svp_user_id',
        'first_name',
        'username',
        'email',
        'svp_department_name',
        'position_name',
        'user_role',
        'is_active',
    ];

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

    public function update(int $id, array $data, string $pk = 'svp_user_id'): bool
    {
        return parent::update($id, $data, $pk);
    }

    public function delete(int $id, string $pk = 'svp_user_id'): bool
    {
        return parent::delete($id, $pk);
    }

    /** นับจำนวนผู้ใช้ทั้งหมด (รองรับคำค้นหา ใช้คำนวณจำนวนหน้า) */
    public function countAll(string $search = ''): int
    {
        $sql = "SELECT COUNT(*) AS total FROM users u WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (u.first_name LIKE :search1 OR u.last_name LIKE :search2
                      OR u.username LIKE :search3 OR u.email LIKE :search4)";
            $params['search1'] = "%{$search}%";
            $params['search2'] = "%{$search}%";
            $params['search3'] = "%{$search}%";
            $params['search4'] = "%{$search}%";
        }

        return (int) $this->query($sql, $params)->fetch()['total'];
    }

    public function paginateWithRelations(int $page = 1, int $perPage = 10, string $sort = 'svp_user_id', string $dir = 'asc', string $search = ''): array
    {
        if (!in_array($sort, self::SORTABLE_COLUMNS, true)) {
            $sort = 'svp_user_id';
        }
        $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';

        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sortColumn = match ($sort) {
            'svp_department_name' => 'd.svp_department_name',
            'position_name'        => 'p.position_name',
            default                 => "u.{$sort}",
        };

        $sql = "SELECT u.svp_user_id, u.username, u.first_name, u.last_name, u.email,
                   u.user_role, u.is_active,
                   d.svp_department_name, p.position_name
            FROM users u
            LEFT JOIN department d ON d.svp_department_id = u.svp_department_id
            LEFT JOIN position p ON p.svp_position_id = u.svp_position_id
            WHERE 1=1";

        $searchValue = $search !== '' ? "%{$search}%" : null;
        if ($searchValue !== null) {
            $sql .= " AND (u.first_name LIKE :search1 OR u.last_name LIKE :search2
                      OR u.username LIKE :search3 OR u.email LIKE :search4)";
        }

        $sql .= " ORDER BY {$sortColumn} {$dir} LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        if ($searchValue !== null) {
            $stmt->bindValue(':search1', $searchValue, PDO::PARAM_STR);
            $stmt->bindValue(':search2', $searchValue, PDO::PARAM_STR);
            $stmt->bindValue(':search3', $searchValue, PDO::PARAM_STR);
            $stmt->bindValue(':search4', $searchValue, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
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
