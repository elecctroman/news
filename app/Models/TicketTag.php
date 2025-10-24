<?php
namespace App\Models;

use PDO;

class TicketTag
{
    public static function all(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT * FROM ticket_tags ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public static function forTicket(PDO $pdo, int $ticketId): array
    {
        $stmt = $pdo->prepare('SELECT tt.* FROM ticket_tags tt INNER JOIN ticket_tag_ticket ttt ON ttt.tag_id = tt.id WHERE ttt.ticket_id = :ticket_id ORDER BY tt.name');
        $stmt->execute(['ticket_id' => $ticketId]);
        return $stmt->fetchAll();
    }

    /**
     * @param array<int> $tagIds
     */
    public static function syncTicketTags(PDO $pdo, int $ticketId, array $tagIds): void
    {
        $pdo->prepare('DELETE FROM ticket_tag_ticket WHERE ticket_id = :ticket_id')->execute(['ticket_id' => $ticketId]);
        if (empty($tagIds)) {
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO ticket_tag_ticket(ticket_id, tag_id) VALUES(:ticket_id, :tag_id)');
        foreach ($tagIds as $tagId) {
            $stmt->execute([
                'ticket_id' => $ticketId,
                'tag_id' => $tagId,
            ]);
        }
    }
}
