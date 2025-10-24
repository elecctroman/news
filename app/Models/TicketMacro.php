<?php
namespace App\Models;

use PDO;

class TicketMacro
{
    public static function allActive(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT * FROM ticket_macros WHERE is_active = 1 ORDER BY title ASC');
        return $stmt->fetchAll();
    }

    public static function find(PDO $pdo, int $macroId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM ticket_macros WHERE id = :id AND is_active = 1');
        $stmt->execute(['id' => $macroId]);
        $macro = $stmt->fetch();
        return $macro ?: null;
    }
}
