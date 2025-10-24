<?php
require __DIR__ . '/../app/Core/Cart.php';

use App\Core\Cart;

session_start();
$cart = new Cart();
$cart->clear();
$cart->add(1, null, 'Ürün A', 'TRY', 50.0, 2, 'key');
$cart->add(1, null, 'Ürün A', 'TRY', 50.0, 1, 'key');
$cart->add(2, 3, 'Ürün B', 'TRY', 100.0, 1, 'account');

assert(count($cart->items()) === 2, 'Sepette 2 kalem olmalı');
assert(abs($cart->subtotal() - 250.0) < 0.001, 'Ara toplam 250 olmalı');
$cart->setCouponCode('DENEME');
assert($cart->couponCode() === 'DENEME', 'Kupon kodu atanmalı');
$cart->clear();
assert($cart->isEmpty(), 'Sepet temizlenmeli');

echo "test_cart başarılı\n";
