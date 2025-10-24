<?php
namespace App\Models;

use PDO;

class Tag
{
    public static function all(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT * FROM tags ORDER BY name ASC');
        return $stmt->fetchAll();
    }
}
