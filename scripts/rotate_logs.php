<?php
declare(strict_types=1);

$logDir = dirname(__DIR__) . '/storage/logs';
$logFile = $logDir . '/app.log';
if (!is_dir($logDir)) {
    echo "Log klasörü bulunamadı\n";
    exit(1);
}
if (!file_exists($logFile)) {
    echo "Dönen log bulunamadı\n";
    exit(0);
}
$timestamp = date('Ymd_His');
$target = $logDir . '/app-' . $timestamp . '.log';
if (!rename($logFile, $target)) {
    echo "Log dosyası taşınamadı\n";
    exit(1);
}
file_put_contents($logFile, '');
echo "Log dosyası $target olarak döndürüldü\n";
