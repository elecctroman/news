<?php
namespace App\Models;

use App\Core\Crypto;
use DateTimeImmutable;
use PDO;

class OrderItemDelivery
{
    public static function create(PDO $pdo, Crypto $crypto, int $orderItemId, string $type, array $payload): int
    {
        $encrypted = $crypto->encrypt(json_encode($payload, JSON_THROW_ON_ERROR));
        $stmt = $pdo->prepare('INSERT INTO order_item_deliveries(order_item_id, delivery_type, encrypted_payload, iv, tag) VALUES(:order_item_id, :type, :payload, :iv, :tag)');
        $stmt->execute([
            'order_item_id' => $orderItemId,
            'type' => $type,
            'payload' => $encrypted['ciphertext'],
            'iv' => $encrypted['iv'],
            'tag' => $encrypted['tag'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function findByOrderItem(PDO $pdo, int $orderItemId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM order_item_deliveries WHERE order_item_id = :order_item_id');
        $stmt->execute(['order_item_id' => $orderItemId]);
        $delivery = $stmt->fetch();
        return $delivery ?: null;
    }

    public static function markViewed(PDO $pdo, int $deliveryId): void
    {
        $pdo->prepare('UPDATE order_item_deliveries SET first_viewed_at = COALESCE(first_viewed_at, NOW()) WHERE id = :id')->execute(['id' => $deliveryId]);
    }

    public static function generateLink(PDO $pdo, int $deliveryId, DateTimeImmutable $expires, string $token): void
    {
        $hash = hash('sha256', $token, true);
        $stmt = $pdo->prepare('INSERT INTO delivery_links(order_item_delivery_id, token_hash, expires_at) VALUES(:delivery_id, :token_hash, :expires)');
        $stmt->execute([
            'delivery_id' => $deliveryId,
            'token_hash' => $hash,
            'expires' => $expires->format('Y-m-d H:i:s'),
        ]);
    }

    public static function consumeLink(PDO $pdo, string $token): ?array
    {
        $hash = hash('sha256', $token, true);
        $stmt = $pdo->prepare('SELECT * FROM delivery_links WHERE token_hash = :hash');
        $stmt->execute(['hash' => $hash]);
        $link = $stmt->fetch();
        if (!$link) {
            return null;
        }
        if (!empty($link['consumed_at'])) {
            return null;
        }
        if (!empty($link['expires_at']) && new DateTimeImmutable($link['expires_at']) < new DateTimeImmutable()) {
            return null;
        }

        $pdo->prepare('UPDATE delivery_links SET consumed_at = NOW() WHERE id = :id')->execute(['id' => $link['id']]);

        $deliveryStmt = $pdo->prepare('SELECT * FROM order_item_deliveries WHERE id = :id');
        $deliveryStmt->execute(['id' => $link['order_item_delivery_id']]);
        $delivery = $deliveryStmt->fetch();
        return $delivery ?: null;
    }
}
