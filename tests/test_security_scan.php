<?php
require __DIR__ . '/../app/Services/SecurityScanner.php';

use App\Services\SecurityScanner;

$scanner = new SecurityScanner(dirname(__DIR__));
$results = $scanner->run();
assert(is_array($results) && count($results) >= 4, 'Tarama sonuçları dönmeli');
assert(in_array($results[0]['status'], ['pass','warn','fail'], true), 'Geçerli durum döndürmeli');

echo "güvenlik taraması testi başarılı\n";
