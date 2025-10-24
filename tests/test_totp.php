<?php
require __DIR__ . '/../app/Core/Totp.php';

use App\Core\Totp;

$secret = Totp::generateSecret();
$timeSlice = (int) floor(time() / 30);
$code = Totp::generateCode($secret, $timeSlice);

if (!Totp::verify($secret, $code, 1)) {
    throw new RuntimeException('TOTP doğrulaması başarısız.');
}

echo "TOTP testi geçti\n";
