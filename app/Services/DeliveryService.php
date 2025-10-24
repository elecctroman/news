<?php
namespace App\Services;

use App\Core\Crypto;
use App\Core\Mailer;
use App\Models\Account;
use App\Models\Order;
use App\Models\OrderItemDelivery;
use App\Models\ProductKey;
use DateInterval;
use DateTimeImmutable;
use PDO;

class DeliveryService
{
    public function __construct(private PDO $pdo, private Crypto $crypto, private ?Mailer $mailer = null)
    {
    }

    /**
     * @return array<int, array>
     */
    public function fulfillOrder(array $order): array
    {
        $full = Order::findWithItems($this->pdo, (int) $order['id']);
        if (!$full) {
            return [];
        }

        $deliveries = [];
        foreach ($full['items'] as $item) {
            $existing = OrderItemDelivery::findByOrderItem($this->pdo, (int) $item['id']);
            if ($existing) {
                $deliveries[] = $existing;
                continue;
            }

            if ($item['item_type'] === 'key') {
                $delivery = $this->deliverKeyItem($item);
            } elseif ($item['item_type'] === 'account') {
                $delivery = $this->deliverAccountItem($item);
            } else {
                $delivery = null;
            }

            if ($delivery) {
                $deliveries[] = $delivery;
            }
        }

        if (!empty($deliveries)) {
            Order::markDelivered($this->pdo, (int) $order['id']);
            $this->mailCustomer((int) $order['user_id'], $order, $deliveries);
        }

        return $deliveries;
    }

    public function getPayload(array $delivery): array
    {
        $json = $this->crypto->decrypt($delivery['encrypted_payload'], $delivery['iv'], $delivery['tag']);
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function generateOneTimeLink(int $deliveryId, int $hours = 12): string
    {
        $token = bin2hex(random_bytes(16));
        $expires = (new DateTimeImmutable())->add(new DateInterval('PT' . $hours . 'H'));
        OrderItemDelivery::generateLink($this->pdo, $deliveryId, $expires, $token);
        return $token;
    }

    private function deliverKeyItem(array $item): ?array
    {
        $stock = ProductKey::reserveAvailable($this->pdo, (int) $item['product_id'], $item['variant_id'] !== null ? (int) $item['variant_id'] : null);
        if (!$stock) {
            return null;
        }
        $plaintext = ProductKey::decrypt($this->crypto, $stock);
        ProductKey::markSold($this->pdo, (int) $stock['id']);
        $payload = [
            'type' => 'key',
            'product' => $item['product_name'],
            'key' => $plaintext,
        ];
        $deliveryId = OrderItemDelivery::create($this->pdo, $this->crypto, (int) $item['id'], 'key', $payload);
        $this->checkLowStock((int) $item['product_id'], 'product_keys', (int) ($item['stock_alert_threshold'] ?? 5), $item['product_name']);
        return OrderItemDelivery::findByOrderItem($this->pdo, (int) $item['id']);
    }

    private function deliverAccountItem(array $item): ?array
    {
        $account = Account::reserveAvailable($this->pdo, (int) $item['product_id'], $item['variant_id'] !== null ? (int) $item['variant_id'] : null);
        if (!$account) {
            return null;
        }
        $payload = Account::decrypt($this->crypto, $account);
        Account::markSold($this->pdo, (int) $account['id']);
        $deliveryId = OrderItemDelivery::create($this->pdo, $this->crypto, (int) $item['id'], 'account', [
            'type' => 'account',
            'credentials' => $payload,
        ]);
        $token = $this->generateOneTimeLink($deliveryId);
        $delivery = OrderItemDelivery::findByOrderItem($this->pdo, (int) $item['id']);
        if ($delivery) {
            $delivery['one_time_token'] = $token;
        }
        $this->checkLowStock((int) $item['product_id'], 'accounts', (int) ($item['stock_alert_threshold'] ?? 5), $item['product_name']);
        return $delivery;
    }

    private function mailCustomer(int $userId, array $order, array $deliveries): void
    {
        if ($this->mailer === null) {
            return;
        }
        $stmt = $this->pdo->prepare('SELECT email, name FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        if (!$user) {
            return;
        }

        $lines = [];
        foreach ($deliveries as $delivery) {
            $payload = $this->getPayload($delivery);
            if ($payload['type'] === 'key') {
                $lines[] = $payload['product'] . ': ' . $payload['key'];
            } elseif ($payload['type'] === 'account') {
                $lines[] = $payload['credentials']['email'] . ' / ' . $payload['credentials']['password'];
            }
        }

        $html = '<p>Siparişiniz teslim edildi.</p><ul>';
        foreach ($lines as $line) {
            $html .= '<li>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $html .= '</ul>';
        $text = "Siparişiniz teslim edildi:\n" . implode("\n", $lines);
        $this->mailer->send($user['email'], 'Sipariş Teslimatı #' . $order['id'], $html, $text);
    }

    private function checkLowStock(int $productId, string $table, int $threshold, string $productName): void
    {
        if ($threshold <= 0) {
            return;
        }
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = :product_id AND status = 'available'");
        $stmt->execute(['product_id' => $productId]);
        $remaining = (int) $stmt->fetchColumn();
        if ($remaining <= $threshold) {
            $_SESSION['order_messages'][] = sprintf('%s için stok kritik seviyede (%d kaldı).', $productName, $remaining);
        }
    }
}
