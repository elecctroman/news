<?php
namespace App\Services\PaymentGateway;

interface PaymentGatewayInterface
{
    /**
     * @param array<string,mixed> $options
     * @return array{reference:string,redirect_url:string}
     */
    public function initiate(float $amount, string $currency, array $options = []): array;

    /**
     * @param array<string,mixed> $payload
     * @return array{status:string,reference:string}
     */
    public function processCallback(array $payload): array;

    public function getName(): string;
}
