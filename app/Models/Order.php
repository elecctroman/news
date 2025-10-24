<?php
namespace App\Models;

use PDO;
use RuntimeException;

class Order
{
    /**
     * @param array<int, array> $items
     */
    public static function createWithItems(PDO $pdo, int $userId, array $items, array $totals, ?array $coupon, string $gateway, string $idempotencyKey): int
    {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO orders(user_id, status, total_amount, discount_amount, currency, coupon_id, payment_gateway, idempotency_key) VALUES(:user_id, :status, :total, :discount, :currency, :coupon_id, :gateway, :idempotency)');
            $stmt->execute([
                'user_id' => $userId,
                'status' => 'payment_pending',
                'total' => $totals['total'],
                'discount' => $totals['discount'],
                'currency' => $totals['currency'],
                'coupon_id' => $coupon['id'] ?? null,
                'gateway' => $gateway,
                'idempotency' => $idempotencyKey,
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO order_items(order_id, product_id, variant_id, quantity, unit_price, subtotal, item_type) VALUES(:order_id, :product_id, :variant_id, :quantity, :unit_price, :subtotal, :type)');
            foreach ($items as $item) {
                $itemStmt->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['unit_price'] * $item['qty'],
                    'type' => $item['type'],
                ]);
            }

            $pdo->commit();
            return $orderId;
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function markPaid(PDO $pdo, int $orderId, ?int $couponId = null): void
    {
        $stmt = $pdo->prepare('UPDATE orders SET status = "paid", coupon_id = COALESCE(coupon_id, :coupon_id), updated_at = NOW() WHERE id = :id AND status IN ("pending","payment_pending")');
        $stmt->execute([
            'id' => $orderId,
            'coupon_id' => $couponId,
        ]);
    }

    public static function markDelivered(PDO $pdo, int $orderId): void
    {
        $pdo->prepare('UPDATE orders SET status = "delivered", updated_at = NOW() WHERE id = :id AND status IN ("paid","payment_pending")')->execute(['id' => $orderId]);
    }

    public static function findById(PDO $pdo, int $orderId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
        $stmt->execute(['id' => $orderId]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    public static function findWithItems(PDO $pdo, int $orderId, ?int $userId = null): ?array
    {
        $sql = 'SELECT * FROM orders WHERE id = :id';
        $params = ['id' => $orderId];
        if ($userId !== null) {
            $sql .= ' AND user_id = :user_id';
            $params['user_id'] = $userId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $order = $stmt->fetch();
        if (!$order) {
            return null;
        }

        $itemStmt = $pdo->prepare('SELECT oi.*, p.name AS product_name, p.stock_alert_threshold, pv.name AS variant_name FROM order_items oi INNER JOIN products p ON p.id = oi.product_id LEFT JOIN product_variants pv ON pv.id = oi.variant_id WHERE oi.order_id = :order_id');
        $itemStmt->execute(['order_id' => $orderId]);
        $order['items'] = $itemStmt->fetchAll();

        return $order;
    }

    public static function listForUser(PDO $pdo, int $userId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function allWithUser(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT o.*, u.email FROM orders o INNER JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 100');
        return $stmt->fetchAll();
    }

    public static function changeStatus(PDO $pdo, int $orderId, string $status): void
    {
        $allowed = ['pending','payment_pending','paid','delivered','refunded','cancelled'];
        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException('Geçersiz durum.');
        }
        $stmt = $pdo->prepare('UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'id' => $orderId,
        ]);
    }

    public static function totalsByDay(PDO $pdo, int $days = 7): array
    {
        $stmt = $pdo->prepare('SELECT DATE(created_at) AS day, SUM(total_amount) AS total FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY) AND status IN ("paid","delivered","refunded") GROUP BY day ORDER BY day ASC');
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
