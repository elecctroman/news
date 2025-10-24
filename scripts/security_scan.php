<?php
declare(strict_types=1);

use App\Services\SecurityScanner;

spl_autoload_register(static function (string $class): void {
    $baseDir = dirname(__DIR__);
    $classPath = $baseDir . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

$scanner = new SecurityScanner(dirname(__DIR__));
$results = $scanner->run();
foreach ($results as $result) {
    echo sprintf("[%s] %s - %s\n", strtoupper($result['status']), $result['name'], $result['message']);
}
