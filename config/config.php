<?php
/**
 * Uygulama yapılandırması.
 *
 * Bu dosya yalnızca manuel olarak düzenlenir; kurulum sihirbazı bulunmaz.
 * Gerekli tüm değerleri kendi barındırma ortamınıza göre güncelleyin.
 */

declare(strict_types=1);

$config = [
    'app' => [
        'name' => 'Dijital Mağaza',
        'url' => 'http://localhost',
        'locale' => 'tr_TR',
        'timezone' => 'Europe/Istanbul',
    ],

    'database' => [
        // Örnek DSN: 'mysql:host=127.0.0.1;dbname=epin;charset=utf8mb4'
        'dsn' => '',
        'user' => '',
        'pass' => '',
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],

    'mail' => [
        'from' => 'no-reply@example.com',
        'smtp' => [
            'host' => '',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
            'timeout' => 30,
        ],
    ],

    'security' => [
        // 32 baytlık anahtar veya "base64:" önekli kodlanmış değer beklenir.
        'encryption_key' => '',
        'passwords' => [
            'algo' => PASSWORD_DEFAULT,
            'options' => [],
        ],
        'headers' => [
            'csp' => "default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self' 'unsafe-inline'",
            'hsts' => true,
            'x_frame_options' => 'SAMEORIGIN',
            'x_content_type_options' => 'nosniff',
            'referrer_policy' => 'no-referrer-when-downgrade',
            'permissions_policy' => 'geolocation=()'
        ],
        'rate_limits' => [
            'login' => ['max_attempts' => 5, 'decay_seconds' => 300],
            'payment' => ['max_attempts' => 10, 'decay_seconds' => 60],
        ],
    ],

    'paths' => [
        'storage' => realpath(__DIR__ . '/../storage') ?: __DIR__ . '/../storage',
        'logs' => __DIR__ . '/../storage/logs',
        'cache' => __DIR__ . '/../storage/cache',
        'backups' => __DIR__ . '/../storage/backups',
        'uploads' => __DIR__ . '/../storage/uploads',
    ],
];

// Eski sürümlerle uyumluluk için "db" anahtarını da sağlayın.
$config['db'] = &$config['database'];

return $config;
