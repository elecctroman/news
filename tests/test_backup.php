<?php
require __DIR__ . '/../app/Services/BackupService.php';

use App\Services\BackupService;

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE sample (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
$pdo->exec("INSERT INTO sample(name) VALUES('demo')");

$service = new BackupService($pdo);
$sql = $service->createSqlDump();
assert(strpos($sql, 'CREATE TABLE') !== false, 'Yedek SQL içermeli');
assert(strpos($sql, 'INSERT INTO `sample`') !== false, 'Yedek veriyi içermeli');

$file = $service->store(sys_get_temp_dir());
assert(is_file($file), 'Yedek dosyası oluşturulmalı');
@unlink($file);

echo "backup servisi testi başarılı\n";
