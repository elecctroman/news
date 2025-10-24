<?php
namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use App\Services\PaymentGateway\BankGateway;
use App\Services\PaymentGateway\MockGateway;
use App\Services\PaymentGateway\PaymentGatewayInterface;
use App\Services\PaymentGateway\WalletGateway;
use PDO;
use RuntimeException;

class PaymentService
{
    public function __construct(private PDO $pdo, private array $config)
    {
    }

    /**
     * @param array<string,mixed> $options
     * @return array{redirect_url:string,reference:string,transaction_id:int}
     */
    public function initiate(array $order, string $gatewayName, array $options = []): array
    {
        $gateway = $this->resolveGateway($gatewayName);
        $options['callback_url'] = $options['callback_url'] ?? '/payment/mock';
        $response = $gateway->initiate((float) $order['total_amount'], (string) $order['currency'], $options);
        $transactionKey = hash('sha256', $order['id'] . microtime(true) . random_bytes(5));
        $transactionId = Transaction::create(
            $this->pdo,
            (int) $order['id'],
            $gateway->getName(),
            (float) $order['total_amount'],
            (string) $order['currency'],
            $transactionKey,
            $response['reference'],
            $options
        );

        return [
            'redirect_url' => $response['redirect_url'],
            'reference' => $response['reference'],
            'transaction_id' => $transactionId,
        ];
    }

    /**
     * @return array{status:string,order:array|null,already:bool}
     */
    public function complete(string $token, string $status): array
    {
        $transaction = Transaction::findByExternalId($this->pdo, $token);
        if (!$transaction) {
            throw new RuntimeException('İşlem bulunamadı.');
        }

        $order = Order::findById($this->pdo, (int) $transaction['order_id']);
        if (!$order) {
            throw new RuntimeException('Sipariş bulunamadı.');
        }

        if ($transaction['status'] === 'succeeded') {
            return ['status' => 'succeeded', 'order' => $order, 'already' => true];
        }

        $gateway = $this->resolveGateway($transaction['gateway']);
        $result = $gateway->processCallback([
            'token' => $token,
            'status' => $status,
        ]);

        if ($result['status'] === 'succeeded') {
            Transaction::markSucceeded($this->pdo, (int) $transaction['id']);
            Order::markPaid($this->pdo, (int) $transaction['order_id'], $order['coupon_id'] !== null ? (int) $order['coupon_id'] : null);
            $order = Order::findById($this->pdo, (int) $transaction['order_id']);
            return ['status' => 'succeeded', 'order' => $order, 'already' => false];
        }

        Transaction::markFailed($this->pdo, (int) $transaction['id']);
        return ['status' => 'failed', 'order' => $order, 'already' => false];
    }

    private function resolveGateway(string $name): PaymentGatewayInterface
    {
        return match ($name) {
            'bank3d' => new BankGateway(),
            'wallet' => new WalletGateway(),
            default => new MockGateway(),
        };
    }
}
