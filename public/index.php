<?php
/**
 * Dijital E-PIN Platformu İskeleti
 *
 * Uygulamanın tek giriş noktasıdır. Kurulum sihirbazı tamamlanana kadar
 * kullanıcıları install ekranına yönlendirir, ardından temel router'ı çalıştırır.
 */

declare(strict_types=1);

use App\Core\Router;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\RateLimiter;
use App\Core\Mailer;
use App\Core\Crypto;
use App\Models\Setting;

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

$envFile = dirname(__DIR__) . '/config/.env.php';
if (!file_exists($envFile)) {
    if (!empty($_GET['install'])) {
        require_once dirname(__DIR__) . '/scripts/install.php';
        exit;
    }
    header('Location: /index.php?install=1');
    exit;
}

if (!empty($_GET['install'])) {
    require_once dirname(__DIR__) . '/scripts/install.php';
    exit;
}

$config = require dirname(__DIR__) . '/config/config.php';

header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self' 'unsafe-inline'");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer-when-downgrade');
header('Permissions-Policy: geolocation=()');
if (!empty($_SERVER['HTTPS'])) {
    header('Strict-Transport-Security: max-age=63072000; includeSubDomains');
}

$pdo = new PDO($config['db']['dsn'], $config['db']['user'], $config['db']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

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
$crypto = new Crypto($config['security']['encryption_key']);

$GLOBALS['auth'] = $auth;
$GLOBALS['csrf'] = $csrf;
$GLOBALS['limiter'] = $limiter;
$GLOBALS['pdo'] = $pdo;
$GLOBALS['config'] = $config;
$GLOBALS['mailer'] = $mailer;
$GLOBALS['crypto'] = $crypto;

require dirname(__DIR__) . '/routes/web.php';

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
