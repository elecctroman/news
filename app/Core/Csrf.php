<?php
namespace App\Core;

class Csrf
{
    private const TOKEN_KEY = '_csrf';

    public function token(): string
    {
        if (!isset($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::TOKEN_KEY];
    }

    public function validate(?string $token): bool
    {
        return hash_equals($_SESSION[self::TOKEN_KEY] ?? '', $token ?? '');
    }

    public function validateToken(?string $token): bool
    {
        return $this->validate($token);
    }

    public function verify(?string $token): bool
    {
        return $this->validate($token);
    }
}
