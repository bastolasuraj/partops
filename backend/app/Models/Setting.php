<?php
namespace App\Models;

use App\Core\Database;

class Setting
{
    private string $table = 'app_settings';

    public function get(string $key, $default = null)
    {
        $row = Database::query(
            "SELECT setting_value FROM {$this->table} WHERE setting_key = ? LIMIT 1",
            [$key]
        )->fetch();

        if (!$row) {
            return $default;
        }

        $raw = $row['setting_value'];
        $decoded = json_decode($raw, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $raw;
    }

    public function getMany(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        $rows = Database::query(
            "SELECT setting_key, setting_value FROM {$this->table} WHERE setting_key IN ({$placeholders})",
            $keys
        )->fetchAll();

        $mapped = [];
        foreach ($rows as $row) {
            $raw = $row['setting_value'];
            $decoded = json_decode($raw, true);
            $mapped[$row['setting_key']] = json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
        }

        return $mapped;
    }

    public function set(string $key, $value): void
    {
        $encoded = json_encode($value);
        Database::query(
            "INSERT INTO {$this->table} (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP",
            [$key, $encoded]
        );
    }
}
