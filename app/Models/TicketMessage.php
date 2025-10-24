<?php
namespace App\Models;

use PDO;

use App\Models\TicketAttachment;

class TicketMessage
{
    /**
     * @param array<int, array{path:string,original_name:string,mime_type:string,size:int}> $attachments
     */
    public static function add(PDO $pdo, int $ticketId, ?int $userId, string $senderType, string $message, array $attachments = []): int
    {
        $stmt = $pdo->prepare('INSERT INTO ticket_messages(ticket_id, user_id, sender_type, message) VALUES(:ticket_id, :user_id, :sender_type, :message)');
        $stmt->execute([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'sender_type' => $senderType,
            'message' => $message,
        ]);

        $messageId = (int) $pdo->lastInsertId();

        foreach ($attachments as $attachment) {
            TicketAttachment::create($pdo, $messageId, $attachment['path'], $attachment['original_name'], $attachment['mime_type'], $attachment['size']);
        }

        return $messageId;
    }

    public static function forTicket(PDO $pdo, int $ticketId): array
    {
        $stmt = $pdo->prepare('SELECT tm.*, u.email FROM ticket_messages tm LEFT JOIN users u ON u.id = tm.user_id WHERE tm.ticket_id = :ticket_id ORDER BY tm.created_at ASC');
        $stmt->execute(['ticket_id' => $ticketId]);
        return $stmt->fetchAll();
    }
}
