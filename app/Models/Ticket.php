<?php
namespace App\Models;

use PDO;
use RuntimeException;

use function in_array;


class Ticket
{
    /**
     * @param array<int, array{path:string,original_name:string,mime_type:string,size:int}> $attachments
     */
    public static function create(PDO $pdo, int $userId, string $subject, string $message, ?int $orderId = null, string $priority = 'normal', array $attachments = [], string $category = 'support'): int
    {
        $pdo->beginTransaction();
        try {
            $status = $category === 'dispute' ? 'dispute' : 'open';
            if (!in_array($category, ['support', 'order', 'payment', 'dispute'], true)) {
                $category = 'support';
            }
            $stmt = $pdo->prepare('INSERT INTO tickets(user_id, order_id, subject, status, priority, category) VALUES(:user_id, :order_id, :subject, :status, :priority, :category)');
            $stmt->execute([
                'user_id' => $userId,
                'order_id' => $orderId,
                'subject' => $subject,
                'status' => $status,
                'priority' => in_array($priority, ['normal', 'urgent'], true) ? $priority : 'normal',
                'category' => $category,
            ]);
            $ticketId = (int) $pdo->lastInsertId();
            TicketMessage::add($pdo, $ticketId, $userId, 'customer', $message, $attachments);
            $pdo->commit();
            return $ticketId;
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * @param array<int, array{path:string,original_name:string,mime_type:string,size:int}> $attachments
     */
    public static function addMessage(PDO $pdo, int $ticketId, ?int $userId, string $senderType, string $message, array $attachments = []): void
    {
        if (!in_array($senderType, ['customer', 'admin', 'system'], true)) {
            throw new RuntimeException('Geçersiz gönderici tipi.');
        }

        $ticket = self::find($pdo, $ticketId);
        TicketMessage::add($pdo, $ticketId, $userId, $senderType, $message, $attachments);

        $status = match ($senderType) {
            'admin' => $ticket && $ticket['status'] === 'dispute' ? 'dispute' : 'waiting_customer',
            'customer' => $ticket && $ticket['status'] === 'closed' ? 'waiting_admin' : 'waiting_admin',
            default => $ticket['status'] ?? 'open',
        };
        if ($ticket && $ticket['status'] === 'dispute' && $senderType === 'customer') {
            $status = 'dispute';
        }
        if ($ticket && $ticket['status'] === 'resolved' && $senderType === 'customer') {
            $status = 'waiting_admin';
        }
        $stmt = $pdo->prepare('UPDATE tickets SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'id' => $ticketId,
        ]);
    }

    public static function changeStatus(PDO $pdo, int $ticketId, string $status): void
    {
        if (!in_array($status, ['open', 'waiting_admin', 'waiting_customer', 'dispute', 'resolved', 'closed'], true)) {
            throw new RuntimeException('Geçersiz bilet durumu.');
        }
        $stmt = $pdo->prepare('UPDATE tickets SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'id' => $ticketId,
        ]);
    }

    public static function updatePriority(PDO $pdo, int $ticketId, string $priority): void
    {
        if (!in_array($priority, ['normal', 'urgent'], true)) {
            throw new RuntimeException('Geçersiz öncelik.');
        }
        $stmt = $pdo->prepare('UPDATE tickets SET priority = :priority WHERE id = :id');
        $stmt->execute([
            'priority' => $priority,
            'id' => $ticketId,
        ]);
    }

    public static function updateCategory(PDO $pdo, int $ticketId, string $category): void
    {
        if (!in_array($category, ['support', 'order', 'payment', 'dispute'], true)) {
            throw new RuntimeException('Geçersiz kategori.');
        }
        $stmt = $pdo->prepare('UPDATE tickets SET category = :category WHERE id = :id');
        $stmt->execute([
            'category' => $category,
            'id' => $ticketId,
        ]);
    }

    public static function listForUser(PDO $pdo, int $userId): array
    {
        $stmt = $pdo->prepare('SELECT t.*, REPLACE(GROUP_CONCAT(tt.name), ",", ", ") AS tag_list FROM tickets t LEFT JOIN ticket_tag_ticket ttt ON ttt.ticket_id = t.id LEFT JOIN ticket_tags tt ON tt.id = ttt.tag_id WHERE t.user_id = :user_id GROUP BY t.id ORDER BY t.updated_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function all(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT t.*, u.email, REPLACE(GROUP_CONCAT(tt.name), ",", ", ") AS tag_list FROM tickets t LEFT JOIN users u ON u.id = t.user_id LEFT JOIN ticket_tag_ticket ttt ON ttt.ticket_id = t.id LEFT JOIN ticket_tags tt ON tt.id = ttt.tag_id GROUP BY t.id ORDER BY t.updated_at DESC LIMIT 200');
        return $stmt->fetchAll();
    }

    public static function find(PDO $pdo, int $ticketId, ?int $userId = null): ?array
    {
        $sql = 'SELECT t.*, u.email FROM tickets t LEFT JOIN users u ON u.id = t.user_id WHERE t.id = :id';
        $params = ['id' => $ticketId];
        if ($userId !== null) {
            $sql .= ' AND t.user_id = :user_id';
            $params['user_id'] = $userId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $ticket = $stmt->fetch();
        if (!$ticket) {
            return null;
        }

        $ticket['messages'] = TicketMessage::forTicket($pdo, $ticketId);
        $attachments = TicketAttachment::forTicket($pdo, $ticketId);
        foreach ($ticket['messages'] as &$message) {
            $messageId = (int) $message['id'];
            $message['attachments'] = $attachments[$messageId] ?? [];
        }
        unset($message);
        $ticket['tags'] = TicketTag::forTicket($pdo, $ticketId);
        return $ticket;
    }

    public static function reopen(PDO $pdo, int $ticketId): void
    {
        $stmt = $pdo->prepare('UPDATE tickets SET status = "open", updated_at = CURRENT_TIMESTAMP WHERE id = :id AND status = "closed"');
        $stmt->execute(['id' => $ticketId]);
    }

    /**
     * @param array<int> $tagIds
     */
    public static function syncTags(PDO $pdo, int $ticketId, array $tagIds): void
    {
        TicketTag::syncTicketTags($pdo, $ticketId, $tagIds);
    }
}
