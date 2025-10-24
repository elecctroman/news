<?php
namespace App\Services\PaymentGateway;

class MockGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'mock';
    }

    public function initiate(float $amount, string $currency, array $options = []): array
    {
        $token = bin2hex(random_bytes(16));
        $callback = $options['callback_url'] ?? '/payment/mock';
        $query = http_build_query([
            'token' => $token,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        return [
            'reference' => $token,
            'redirect_url' => $callback . '?' . $query,
        ];
    }

    public function processCallback(array $payload): array
    {
        $status = strtolower((string) ($payload['status'] ?? 'failed'));
        $token = (string) ($payload['token'] ?? '');
        return [
            'status' => $status === 'success' ? 'succeeded' : 'failed',
            'reference' => $token,
        ];
    }
}
