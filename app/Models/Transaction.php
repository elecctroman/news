<?php
namespace App\Models;

use PDO;

class Transaction
{
    public static function create(PDO $pdo, int $orderId, string $gateway, float $amount, string $currency, string $idempotencyKey, ?string $externalId = null, ?array $payload = null): int
    {
        $stmt = $pdo->prepare('INSERT INTO transactions(order_id, gateway, external_id, status, amount, currency, idempotency_key, payload) VALUES(:order_id, :gateway, :external_id, :status, :amount, :currency, :idempotency_key, :payload)');
        $stmt->execute([
            'order_id' => $orderId,
            'gateway' => $gateway,
            'external_id' => $externalId,
            'status' => 'initiated',
            'amount' => $amount,
            'currency' => $currency,
            'idempotency_key' => $idempotencyKey,
            'payload' => $payload ? json_encode($payload, JSON_THROW_ON_ERROR) : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function markSucceeded(PDO $pdo, int $transactionId, ?string $externalId = null): void
    {
        $stmt = $pdo->prepare('UPDATE transactions SET status = "succeeded", external_id = COALESCE(:external_id, external_id), updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $transactionId,
            'external_id' => $externalId,
        ]);
    }

    public static function markFailed(PDO $pdo, int $transactionId, ?string $externalId = null): void
    {
        $stmt = $pdo->prepare('UPDATE transactions SET status = "failed", external_id = COALESCE(:external_id, external_id), updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $transactionId,
            'external_id' => $externalId,
        ]);
    }

    public static function findByExternalId(PDO $pdo, string $externalId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM transactions WHERE external_id = :external_id');
        $stmt->execute(['external_id' => $externalId]);
        $transaction = $stmt->fetch();
        return $transaction ?: null;
    }

    public static function find(PDO $pdo, int $transactionId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = :id');
        $stmt->execute(['id' => $transactionId]);
        $transaction = $stmt->fetch();
        return $transaction ?: null;
    }
}
