<?php
namespace App\Models;

use PDO;

class TicketAttachment
{
    public static function create(PDO $pdo, int $messageId, string $path, string $originalName, string $mimeType, int $size): void
    {
        $stmt = $pdo->prepare('INSERT INTO ticket_attachments(message_id, path, original_name, mime_type, size) VALUES(:message_id, :path, :original_name, :mime_type, :size)');
        $stmt->execute([
            'message_id' => $messageId,
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => $size,
        ]);
    }

    /**
     * @return array<int, array<int, array<string,mixed>>>
     */
    public static function forTicket(PDO $pdo, int $ticketId): array
    {
        $stmt = $pdo->prepare('SELECT ta.*, tm.ticket_id FROM ticket_attachments ta INNER JOIN ticket_messages tm ON tm.id = ta.message_id WHERE tm.ticket_id = :ticket_id ORDER BY ta.created_at ASC');
        $stmt->execute(['ticket_id' => $ticketId]);
        $grouped = [];
        while ($row = $stmt->fetch()) {
            $grouped[(int) $row['message_id']][] = $row;
        }
        return $grouped;
    }

    public static function findForUser(PDO $pdo, int $attachmentId, int $userId): ?array
    {
        $stmt = $pdo->prepare('SELECT ta.* FROM ticket_attachments ta INNER JOIN ticket_messages tm ON tm.id = ta.message_id INNER JOIN tickets t ON t.id = tm.ticket_id WHERE ta.id = :id AND t.user_id = :user_id');
        $stmt->execute([
            'id' => $attachmentId,
            'user_id' => $userId,
        ]);
        $attachment = $stmt->fetch();
        return $attachment ?: null;
    }

    public static function find(PDO $pdo, int $attachmentId): ?array
    {
        $stmt = $pdo->prepare('SELECT ta.*, tm.ticket_id, t.user_id FROM ticket_attachments ta INNER JOIN ticket_messages tm ON tm.id = ta.message_id INNER JOIN tickets t ON t.id = tm.ticket_id WHERE ta.id = :id');
        $stmt->execute(['id' => $attachmentId]);
        $attachment = $stmt->fetch();
        return $attachment ?: null;
    }
}
