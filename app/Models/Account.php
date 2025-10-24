<?php
namespace App\Models;

use App\Core\Crypto;
use PDO;

class Account
{
    public static function importFromCsv(PDO $pdo, Crypto $crypto, int $productId, ?int $variantId, string $csvContent): int
    {
        $rows = str_getcsv($csvContent, "\n");
        $stmt = $pdo->prepare('INSERT INTO accounts(product_id, variant_id, encrypted_payload, iv, tag) VALUES(:product_id, :variant_id, :payload, :iv, :tag)');
        $count = 0;

        foreach ($rows as $row) {
            $row = trim($row);
            if ($row === '') {
                continue;
            }
            $columns = str_getcsv($row, ',');
            $payload = [
                'email' => $columns[0] ?? '',
                'password' => $columns[1] ?? '',
                'backup' => $columns[2] ?? '',
            ];
            $encrypted = $crypto->encrypt(json_encode($payload, JSON_THROW_ON_ERROR));
            $stmt->execute([
                'product_id' => $productId,
                'variant_id' => $variantId,
                'payload' => $encrypted['ciphertext'],
                'iv' => $encrypted['iv'],
                'tag' => $encrypted['tag'],
            ]);
            $count++;
        }

        return $count;
    }

    public static function reserveAvailable(PDO $pdo, int $productId, ?int $variantId): ?array
    {
        $sql = 'SELECT * FROM accounts WHERE product_id = :product_id AND status = "available"';
        $params = ['product_id' => $productId];
        if ($variantId !== null) {
            $sql .= ' AND (variant_id = :variant_id OR variant_id IS NULL)';
            $params['variant_id'] = $variantId;
        }
        $sql .= ' ORDER BY id ASC LIMIT 1 FOR UPDATE';

        $started = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $started = true;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $account = $stmt->fetch();
        if (!$account) {
            if ($started) {
                $pdo->rollBack();
            }
            return null;
        }

        $pdo->prepare('UPDATE accounts SET status = "reserved" WHERE id = :id')->execute(['id' => $account['id']]);
        if ($started) {
            $pdo->commit();
        }

        return $account;
    }

    public static function markSold(PDO $pdo, int $accountId): void
    {
        $pdo->prepare('UPDATE accounts SET status = "sold", delivered_at = NOW() WHERE id = :id')->execute(['id' => $accountId]);
    }

    public static function decrypt(Crypto $crypto, array $record): array
    {
        $decoded = $crypto->decrypt($record['encrypted_payload'], $record['iv'], $record['tag']);
        return json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
    }
}
