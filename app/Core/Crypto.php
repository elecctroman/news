<?php
namespace App\Core;

use RuntimeException;

/**
 * AES-256-GCM ile şifreleme/deşifreleme yardımcı sınıfı.
 */
class Crypto
{
    public function __construct(private string $key)
    {
        if (strlen($this->key) !== 32) {
            throw new RuntimeException('AES-256 için anahtar 32 bayt olmalıdır.');
        }
    }

    public function encrypt(string $plaintext, string $aad = ''): array
    {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad,
            16
        );

        if ($cipher === false) {
            throw new RuntimeException('Veri şifrelenemedi.');
        }

        return [
            'ciphertext' => $cipher,
            'iv' => $iv,
            'tag' => $tag,
        ];
    }

    public function decrypt(string $ciphertext, string $iv, string $tag, string $aad = ''): string
    {
        $plain = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );

        if ($plain === false) {
            throw new RuntimeException('Veri çözülemedi.');
        }

        return $plain;
    }
}
