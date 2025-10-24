<?php
namespace App\Core;

use DateInterval;
use DateTimeImmutable;

/**
 * Basit dosya tabanlı rate limiter örneği.
 */
class RateLimiter
{
    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = dirname(__DIR__, 2) . '/storage/cache/ratelimiter.json';
        if (!file_exists($this->storagePath)) {
            if (!is_dir(dirname($this->storagePath))) {
                mkdir(dirname($this->storagePath), 0755, true);
            }
            file_put_contents($this->storagePath, json_encode([]));
        }
    }

    public function hit(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $now = new DateTimeImmutable();
        $expiry = $now->sub(new DateInterval('PT' . $decaySeconds . 'S'));

        $data = json_decode((string) file_get_contents($this->storagePath), true) ?: [];
        $attempts = $data[$key] ?? [];
        $attempts = array_filter($attempts, static fn(string $timestamp): bool => $timestamp >= $expiry->format('U'));

        if (count($attempts) >= $maxAttempts) {
            return false;
        }

        $attempts[] = $now->format('U');
        $data[$key] = array_values($attempts);
        file_put_contents($this->storagePath, json_encode($data, JSON_PRETTY_PRINT));

        return true;
    }
}
