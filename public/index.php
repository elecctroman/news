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

$config = require dirname(__DIR__) . '/config/config.php';

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
    echo '<p><strong>config/config.php</strong> dosyasını düzenleyerek veritabanı ve şifreleme ayarlarını tamamlayın.</p>';
    if ($issues !== []) {
        echo '<ul>';
        foreach ($issues as $issue) {
            echo '<li>' . htmlspecialchars($issue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
        }
        echo '</ul>';
    }
    echo '<p>Örnek DSN: <code>mysql:host=127.0.0.1;dbname=epin;charset=utf8mb4</code></p>';
    echo '<p>Şifreleme anahtarı olarak 32 baytlık bir değer veya <code>base64:</code> ile başlayan kodlanmış anahtar kullanın.</p>';
    if ($error !== null) {
        echo '<div class="error"><strong>Teknik detay:</strong> ' . htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>';
    }
    echo '</div></body></html>';
    exit;
}

$issues = [];
$database = $config['database'] ?? ($config['db'] ?? []);
$dbDsn = $database['dsn'] ?? '';
$dbUser = $database['user'] ?? '';
$encryptionKey = $config['security']['encryption_key'] ?? '';

if ($dbDsn === '') {
    $issues[] = 'Veritabanı DSN değeri boş.';
}
if ($dbUser === '') {
    $issues[] = 'Veritabanı kullanıcı adı tanımlanmalı.';
}
if ($encryptionKey === '') {
    $issues[] = 'Şifreleme anahtarını tanımlayın (32 bayt veya base64 kodlu).';
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
