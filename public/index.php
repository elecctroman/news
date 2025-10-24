<?php
/**
 * Dijital E-PIN Platformu İskeleti
 *
 * Uygulamanın tek giriş noktasıdır. Yapılandırma dosyası tamamlanana kadar
 * kullanıcıya bilgilendirici bir kurulum uyarısı gösterir, ardından router'ı çalıştırır.
 */

declare(strict_types=1);

use App\Core\Router;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\RateLimiter;
use App\Core\Mailer;
use App\Core\Crypto;
use App\Models\Setting;
use Throwable;

spl_autoload_register(static function (string $class): void {
    $baseDir = dirname(__DIR__);
    $classPath = $baseDir . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true,
    'cookie_samesite' => 'Lax',
]);

$rawConfig = require dirname(__DIR__) . '/config/config.php';

$dbDefaults = [
    'host' => '',
    'dbname' => '',
    'user' => '',
    'pass' => '',
    'charset' => 'utf8mb4',
];

$dbConfig = array_merge($dbDefaults, array_intersect_key($rawConfig, $dbDefaults));

$appConfig = [
    'name' => $rawConfig['app_name'] ?? 'Dijital Mağaza',
    'url' => $rawConfig['app_url'] ?? 'http://localhost',
    'locale' => $rawConfig['locale'] ?? 'tr_TR',
    'timezone' => $rawConfig['timezone'] ?? 'Europe/Istanbul',
];

$mailFrom = $rawConfig['mail_from'] ?? 'no-reply@' . (parse_url($appConfig['url'], PHP_URL_HOST) ?: 'localhost');

$dsn = '';
if ($dbConfig['host'] !== '' && $dbConfig['dbname'] !== '') {
    $charset = $dbConfig['charset'] !== '' ? $dbConfig['charset'] : 'utf8mb4';
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['dbname'], $charset);
}

$config = [
    'app' => $appConfig,
    'database' => [
        'dsn' => $dsn,
        'user' => $dbConfig['user'],
        'pass' => $dbConfig['pass'],
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
    'mail' => [
        'from' => $mailFrom,
    ],
    'security' => [
        'encryption_key' => hash('sha256', implode('|', [
            $dbConfig['user'],
            $dbConfig['pass'],
            $dbConfig['dbname'],
            $dbConfig['host'],
        ]), true),
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

$config['db'] = &$config['database'];

date_default_timezone_set($config['app']['timezone'] ?? 'UTC');
if (isset($config['app']['locale'])) {
    setlocale(LC_ALL, $config['app']['locale'] . '.UTF-8', $config['app']['locale']);
}

/**
 * Yapılandırma eksikse veya hatalıysa kullanıcıya gösterilecek mesaj.
 *
 * @param list<string> $issues
 */
function renderSetupNotice(array $issues, ?Throwable $error = null): void
{
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8">';
    echo '<title>Yapılandırma Gerekli</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f8fafc;color:#1f2937;padding:40px;}';
    echo '.card{max-width:720px;margin:0 auto;background:#fff;border-radius:12px;box-shadow:0 10px 25px rgba(15,23,42,.1);padding:32px;}';
    echo 'h1{font-size:28px;margin-bottom:16px;}ul{margin:16px 0;padding-left:20px;}code{background:#e2e8f0;padding:2px 6px;border-radius:6px;}';
    echo '.error{margin-top:24px;font-size:14px;color:#b91c1c;background:#fee2e2;padding:12px 16px;border-radius:8px;}</style>';
    echo '</head><body><div class="card">';
    echo '<h1>Yapılandırma Gerekli</h1>';
    echo '<p><strong>config/config.php</strong> dosyasını düzenleyerek veritabanı bağlantı ayarlarını tamamlayın.</p>';
    if ($issues !== []) {
        echo '<ul>';
        foreach ($issues as $issue) {
            echo '<li>' . htmlspecialchars($issue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
        }
        echo '</ul>';
    }
    echo '<p>Örnek değerler:</p>';
    echo '<pre><code>' . htmlspecialchars("return [\n    'host' => '127.0.0.1',\n    'dbname' => 'app',\n    'user' => 'app',\n    'pass' => 'secret',\n    'charset' => 'utf8mb4',\n];", ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>';
    if ($error !== null) {
        echo '<div class="error"><strong>Teknik detay:</strong> ' . htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>';
    }
    echo '</div></body></html>';
    exit;
}

$issues = [];
$database = $config['database'];
$dbDsn = $database['dsn'];
$dbUser = $database['user'];

if ($dbConfig['host'] === '') {
    $issues[] = 'Veritabanı sunucu adresini tanımlayın.';
}
if ($dbConfig['dbname'] === '') {
    $issues[] = 'Veritabanı adı boş olamaz.';
}
if ($database['user'] === '') {
    $issues[] = 'Veritabanı kullanıcı adı tanımlanmalı.';
}
if ($database['pass'] === '') {
    $issues[] = 'Veritabanı parolasını girin.';
}

if ($issues !== []) {
    renderSetupNotice($issues);
}

$securityHeaders = $config['security']['headers'] ?? [];
if (!empty($securityHeaders['csp'])) {
    header('Content-Security-Policy: ' . $securityHeaders['csp']);
}
if (!empty($securityHeaders['x_content_type_options'])) {
    header('X-Content-Type-Options: ' . $securityHeaders['x_content_type_options']);
}
if (!empty($securityHeaders['x_frame_options'])) {
    header('X-Frame-Options: ' . $securityHeaders['x_frame_options']);
}
if (!empty($securityHeaders['referrer_policy'])) {
    header('Referrer-Policy: ' . $securityHeaders['referrer_policy']);
}
if (!empty($securityHeaders['permissions_policy'])) {
    header('Permissions-Policy: ' . $securityHeaders['permissions_policy']);
}
if (($securityHeaders['hsts'] ?? false) && !empty($_SERVER['HTTPS'])) {
    header('Strict-Transport-Security: max-age=63072000; includeSubDomains');
}

$pdo = null;
try {
    $defaultOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdoOptions = ($database['options'] ?? []) + $defaultOptions;
    $pdo = new PDO($dbDsn, $dbUser, $database['pass'] ?? '', $pdoOptions);
} catch (Throwable $e) {
    renderSetupNotice(['Veritabanı bağlantısı başarısız oldu.'], $e);
}

$maintenanceMode = null;
$whitelist = [];
try {
    $maintenanceMode = Setting::get($pdo, 'maintenance_mode', 'off');
    $whitelistString = Setting::get($pdo, 'maintenance_whitelist', '') ?? '';
    $whitelist = array_filter(array_map('trim', explode(',', $whitelistString)));
} catch (\Throwable $e) {
    $maintenanceMode = 'off';
}

$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$isAdminRoute = str_starts_with($_SERVER['REQUEST_URI'] ?? '/', '/admin');
if ($maintenanceMode === 'on' && !$isAdminRoute && !in_array($clientIp, $whitelist, true)) {
    $page = dirname(__DIR__) . '/public/maintenance.html';
    if (is_file($page)) {
        readfile($page);
    } else {
        echo 'Sistem bakım modunda.';
    }
    exit;
}

$router = new Router();
$auth = new Auth($pdo);
$csrf = new Csrf();
$limiter = new RateLimiter();
$mailer = new Mailer($config);
try {
    $encryptionKey = (string)($config['security']['encryption_key'] ?? '');
    $crypto = new Crypto($encryptionKey);
} catch (Throwable $e) {
    renderSetupNotice(['Şifreleme anahtarı geçerli değil. 32 bayt uzunluğunda olmalıdır.'], $e);
}

$GLOBALS['auth'] = $auth;
$GLOBALS['csrf'] = $csrf;
$GLOBALS['limiter'] = $limiter;
$GLOBALS['pdo'] = $pdo;
$GLOBALS['config'] = $config;
$GLOBALS['mailer'] = $mailer;
$GLOBALS['crypto'] = $crypto;

require dirname(__DIR__) . '/routes/web.php';

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
