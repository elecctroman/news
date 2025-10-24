<?php
require __DIR__ . '/../app/Core/Crypto.php';

use App\Core\Crypto;

$key = random_bytes(32);
$crypto = new Crypto($key);
$plaintext = 'Test verisi ' . bin2hex(random_bytes(4));
$payload = $crypto->encrypt($plaintext);
$decrypted = $crypto->decrypt($payload['ciphertext'], $payload['iv'], $payload['tag']);

if ($decrypted !== $plaintext) {
    throw new RuntimeException('Şifreli veri çözümlenemedi.');
}

echo "Crypto testi geçti\n";
