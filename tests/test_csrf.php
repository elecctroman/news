<?php
session_start();

require __DIR__ . '/../app/Core/Csrf.php';

use App\Core\Csrf;

$csrf = new Csrf();
$token = $csrf->token();

assert(is_string($token) && strlen($token) > 20, 'Token üretilemedi');
assert($csrf->validate($token) === true, 'Token doğrulanamadı');

echo "CSRF testi başarılı\n";
