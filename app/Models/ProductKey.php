<?php
namespace App\Models;

use App\Core\Crypto;
use PDO;

class ProductKey
{
    public static function importFromCsv(PDO $pdo, Crypto $crypto, int $productId, ?int $variantId, string $csvContent): int
    {
        $rows = str_getcsv($csvContent, "\n");
        $stmt = $pdo->prepare('INSERT INTO product_keys(product_id, variant_id, encrypted_key, iv, tag) VALUES(:product_id, :variant_id, :encrypted_key, :iv, :tag)');
        $count = 0;

        foreach ($rows as $row) {
            $row = trim($row);
            if ($row === '') {
                continue;
            }
            $payload = $crypto->encrypt($row);
            $stmt->execute([
                'product_id' => $productId,
                'variant_id' => $variantId,
                'encrypted_key' => $payload['ciphertext'],
                'iv' => $payload['iv'],
                'tag' => $payload['tag'],
            ]);
            $count++;
        }

        return $count;
    }

    public static function reserveAvailable(PDO $pdo, int $productId, ?int $variantId): ?array
    {
        $sql = 'SELECT * FROM product_keys WHERE product_id = :product_id AND status = "available"';
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
        $key = $stmt->fetch();
        if (!$key) {
            if ($started) {
                $pdo->rollBack();
            }
            return null;
        }

        $update = $pdo->prepare('UPDATE product_keys SET status = "reserved" WHERE id = :id');
        $update->execute(['id' => $key['id']]);
        if ($started) {
            $pdo->commit();
        }
        return $key;
    }

    public static function markSold(PDO $pdo, int $keyId): void
    {
        $pdo->prepare('UPDATE product_keys SET status = "sold", delivered_at = NOW() WHERE id = :id')->execute(['id' => $keyId]);
    }

    public static function decrypt(Crypto $crypto, array $record): string
    {
        return $crypto->decrypt($record['encrypted_key'], $record['iv'], $record['tag']);
    }
}
