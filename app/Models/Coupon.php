<?php
namespace App\Models;

use PDO;

class Coupon
{
    public static function findActiveByCode(PDO $pdo, string $code): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code = :code AND status = "active"');
        $stmt->execute(['code' => strtoupper($code)]);
        $coupon = $stmt->fetch();
        if (!$coupon) {
            return null;
        }

        $now = new \DateTimeImmutable();
        if (!empty($coupon['starts_at']) && $now < new \DateTimeImmutable($coupon['starts_at'])) {
            return null;
        }
        if (!empty($coupon['ends_at']) && $now > new \DateTimeImmutable($coupon['ends_at'])) {
            return null;
        }
        if ($coupon['usage_limit'] !== null && (int) $coupon['used_count'] >= (int) $coupon['usage_limit']) {
            return null;
        }

        return $coupon;
    }

    public static function calculateDiscount(array $coupon, float $subtotal): float
    {
        if ($subtotal < (float) $coupon['min_total']) {
            return 0.0;
        }

        if ($coupon['type'] === 'percent') {
            $discount = $subtotal * ((float) $coupon['value'] / 100);
        } else {
            $discount = (float) $coupon['value'];
        }

        $discount = min($discount, $subtotal);
        return round($discount, 2);
    }

    public static function incrementUsage(PDO $pdo, int $couponId): void
    {
        $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = :id')->execute(['id' => $couponId]);
    }

    public static function all(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT * FROM coupons ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public static function create(PDO $pdo, array $data): void
    {
        $stmt = $pdo->prepare('INSERT INTO coupons(code, type, value, usage_limit, min_total, starts_at, ends_at, status) VALUES(:code, :type, :value, :usage_limit, :min_total, :starts_at, :ends_at, :status)');
        $stmt->execute([
            'code' => strtoupper($data['code']),
            'type' => $data['type'],
            'value' => $data['value'],
            'usage_limit' => $data['usage_limit'] !== '' ? (int) $data['usage_limit'] : null,
            'min_total' => $data['min_total'] ?? 0,
            'starts_at' => $data['starts_at'] ?: null,
            'ends_at' => $data['ends_at'] ?: null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $stmt = $pdo->prepare('DELETE FROM coupons WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
