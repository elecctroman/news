<?php
namespace App\Models;

use PDO;

class Setting
{
    public static function get(PDO $pdo, string $key, ?string $default = null): ?string
    {
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();
        if ($value === false) {
            return $default;
        }
        return (string) $value;
    }

    public static function set(PDO $pdo, string $key, string $value): void
    {
        $stmt = $pdo->prepare('INSERT INTO settings(setting_key, setting_value) VALUES(:key, :value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $stmt->execute([
            'key' => $key,
            'value' => $value,
        ]);
    }

    public static function all(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
        $items = [];
        while ($row = $stmt->fetch()) {
            $items[$row['setting_key']] = $row['setting_value'];
        }
        return $items;
    }
}
