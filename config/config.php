<?php
/**
 * Uygulama yapılandırması.
 *
 * Bu dosya kurulum sırasında manuel olarak düzenlenmelidir.
 * Veritabanı DSN, kullanıcı adı/şifre ve 32 baytlık şifreleme anahtarını
 * aşağıdaki alanlara giriniz. AES anahtarı için `base64:` öneki ile 32 baytlık
 * bir değer kullanabilirsiniz (örn. `base64:...`).
 */

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Dijital Mağaza',
        'url' => 'http://localhost',
    ],
    'db' => [
        // Örnek: 'mysql:host=127.0.0.1;dbname=epin;charset=utf8mb4'
        'dsn' => '',
        'user' => '',
        'pass' => '',
    ],
    'mail' => [
        'from' => 'no-reply@example.com',
    ],
    'security' => [
        // 32 baytlık anahtar veya "base64:" ile başlayan kodlanmış değer
        'encryption_key' => '',
    ],
];
