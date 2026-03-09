<?php
namespace App\Core;

abstract class BaseModel
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    
    public function all(): array
    {
        $stmt = Database::query("SELECT * FROM {$this->table} ORDER BY {$this->primaryKey} DESC");
        return $stmt->fetchAll();
    }
    
    public function find(int $id): ?array
    {
        $stmt = Database::query(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function create(array $data): array
    {
        $data = $this->filterFillable($data);
        
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        Database::query(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})",
            array_values($data)
        );
        
        $id = Database::lastInsertId();
        return $this->find((int)$id);
    }
    
    public function update(int $id, array $data): ?array
    {
        $data = $this->filterFillable($data);
        
        if (empty($data)) {
            return $this->find($id);
        }
        
        $sets = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $id;
        
        Database::query(
            "UPDATE {$this->table} SET {$sets} WHERE {$this->primaryKey} = ?",
            $values
        );
        
        return $this->find($id);
    }
    
    public function delete(int $id): bool
    {
        $stmt = Database::query(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
        return $stmt->rowCount() > 0;
    }
    
    public function where(string $column, $value): array
    {
        $stmt = Database::query(
            "SELECT * FROM {$this->table} WHERE {$column} = ?",
            [$value]
        );
        return $stmt->fetchAll();
    }
    
    public function findBy(string $column, $value): ?array
    {
        $stmt = Database::query(
            "SELECT * FROM {$this->table} WHERE {$column} = ? LIMIT 1",
            [$value]
        );
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    public function count(): int
    {
        $stmt = Database::query("SELECT COUNT(*) as count FROM {$this->table}");
        return (int)$stmt->fetch()['count'];
    }
}
