<?php
namespace App\Core;

class Cache
{
    public function __construct(private string $path)
    {
    }

    public function get(string $key): mixed
    {
        $file = $this->path . '/' . md5($key) . '.cache';
        if (!file_exists($file)) {
            return null;
        }

        $content = json_decode((string) file_get_contents($file), true);
        if (($content['expires_at'] ?? 0) < time()) {
            unlink($file);
            return null;
        }

        return $content['value'];
    }

    public function put(string $key, mixed $value, int $ttl): void
    {
        $file = $this->path . '/' . md5($key) . '.cache';
        file_put_contents($file, json_encode([
            'value' => $value,
            'expires_at' => time() + $ttl,
        ]));
    }
}
