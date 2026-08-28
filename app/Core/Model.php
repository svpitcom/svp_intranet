<?php
abstract class Model
{
    protected PDO $db;
    protected string $table;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function find(int $id, string $pk = 'id'): ?array
    {
        $row = $this->query("SELECT * FROM {$this->table} WHERE {$pk} = :id LIMIT 1", ['id' => $id])->fetch();
        return $row ?: null;
    }

    public function all(string $orderBy = ''): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy) $sql .= " ORDER BY {$orderBy}";
        return $this->query($sql)->fetchAll();
    }

    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $this->query("INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})", $data);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data, string $pk = 'id'): bool
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = :{$col}", array_keys($data)));
        $data['pk_value'] = $id;
        return $this->query("UPDATE {$this->table} SET {$set} WHERE {$pk} = :pk_value", $data)->rowCount() >= 0;
    }

    public function delete(int $id, string $pk = 'id'): bool
    {
        return $this->query("DELETE FROM {$this->table} WHERE {$pk} = :id", ['id' => $id])->rowCount() > 0;
    }
}
