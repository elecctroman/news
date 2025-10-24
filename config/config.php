<?php
/**
 * Uygulama yapılandırması.
 *
 * Bu dosya .env.php dosyasını okuyarak yapılandırma değerlerini üretir.
 */

declare(strict_types=1);

$envPath = __DIR__ . '/.env.php';
if (!file_exists($envPath)) {
    throw new \RuntimeException('Lütfen önce kurulum sihirbazını çalıştırarak .env.php dosyasını oluşturun.');
}

$env = require $envPath;

return [
    'app' => [
        'name' => $env['APP_NAME'] ?? 'Dijital Mağaza',
        'url' => $env['APP_URL'] ?? 'http://localhost',
    ],
    'db' => [
        'dsn' => sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $env['DB_HOST'] ?? 'localhost',
            $env['DB_NAME'] ?? 'epin'
        ),
        'user' => $env['DB_USER'] ?? 'root',
        'pass' => $env['DB_PASS'] ?? '',
    ],
    'mail' => [
        'from' => $env['MAIL_FROM'] ?? 'no-reply@example.com',
    ],
    'security' => [
        'encryption_key' => $env['ENCRYPTION_KEY'] ?? '',
    ],
];
