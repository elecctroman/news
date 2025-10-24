<?php
namespace App\Models;

use PDO;

class Category
{
    public static function all(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT * FROM categories ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public static function findBySlug(PDO $pdo, string $slug): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }
}
