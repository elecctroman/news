<?php
require __DIR__ . '/../app/Models/Coupon.php';

use App\Models\Coupon;

$pdo = new PDO('sqlite::memory:');
$pdo->exec('CREATE TABLE coupons (id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT, type TEXT, value REAL, usage_limit INTEGER, used_count INTEGER DEFAULT 0, min_total REAL DEFAULT 0, starts_at TEXT, ends_at TEXT, status TEXT)');
$pdo->prepare('INSERT INTO coupons(code,type,value,usage_limit,min_total,status) VALUES("TEST10","percent",10,NULL,0,"active")')->execute();
$coupon = Coupon::findActiveByCode($pdo, 'test10');
assert($coupon !== null, 'Kupon bulunmalı');
$discount = Coupon::calculateDiscount($coupon, 200);
assert(abs($discount - 20.0) < 0.001, 'İndirim 20 olmalı');
Coupon::incrementUsage($pdo, (int) $coupon['id']);
$updated = $pdo->query('SELECT used_count FROM coupons WHERE id = ' . (int) $coupon['id'])->fetchColumn();
assert((int) $updated === 1, 'Kullanım sayısı artmalı');
echo "test_coupon başarılı\n";
