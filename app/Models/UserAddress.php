<?php
namespace App\Models;

use PDO;

class UserAddress
{
    public static function list(PDO $pdo, int $userId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function save(PDO $pdo, int $userId, array $data): void
    {
        $stmt = $pdo->prepare('INSERT INTO user_addresses(user_id, type, full_name, line1, line2, city, country, tax_number) VALUES(:user_id, :type, :full_name, :line1, :line2, :city, :country, :tax_number)');
        $stmt->execute([
            'user_id' => $userId,
            'type' => $data['type'] ?? 'billing',
            'full_name' => $data['full_name'],
            'line1' => $data['line1'],
            'line2' => $data['line2'] ?? null,
            'city' => $data['city'],
            'country' => $data['country'],
            'tax_number' => $data['tax_number'] ?? null,
        ]);
    }
}
