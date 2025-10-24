<?php
require __DIR__ . '/../app/Core/RateLimiter.php';

use App\Core\RateLimiter;

$limiter = new RateLimiter();
$key = 'test:' . bin2hex(random_bytes(4));

$allowed = 0;
for ($i = 0; $i < 3; $i++) {
    if ($limiter->hit($key, 2, 60)) {
        $allowed++;
    }
}

assert($allowed === 2, 'Rate limiter beklenen şekilde sınırlandırmadı');

echo "Rate limiter testi başarılı\n";
