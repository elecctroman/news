<section class="cart">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php if ($message): ?>
        <div class="alert alert-success" role="status"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($cart)): ?>
        <p>Sepetiniz boş. <a href="/katalog">Ürünlere göz atın</a>.</p>
    <?php else: ?>
        <div class="cart-grid">
            <div class="cart-table" aria-labelledby="cart-items">
                <h2 id="cart-items">Sepet Kalemleri</h2>
                <table class="table">
                    <thead>
                    <tr><th>Ürün</th><th>Adet</th><th>Birim Fiyat</th><th>Toplam</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cart as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td>
                                <input type="number" min="0" name="quantity" value="<?= (int) $item['qty'] ?>" form="update-<?= htmlspecialchars($item['row_id']) ?>">
                            </td>
                            <td><?= number_format((float) $item['unit_price'], 2, ',', '.') ?> <?= htmlspecialchars($item['currency']) ?></td>
                            <td><?= number_format((float) $item['unit_price'] * (int) $item['qty'], 2, ',', '.') ?> <?= htmlspecialchars($item['currency']) ?></td>
                            <td>
                                <form id="update-<?= htmlspecialchars($item['row_id']) ?>" method="post" action="/cart/update" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
                                    <input type="hidden" name="row_id" value="<?= htmlspecialchars($item['row_id']) ?>">
                                    <button class="button-small" type="submit">Güncelle</button>
                                </form>
                                <form method="post" action="/cart/remove" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
                                    <input type="hidden" name="row_id" value="<?= htmlspecialchars($item['row_id']) ?>">
                                    <button class="button-link" type="submit">Kaldır</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <aside class="cart-summary" aria-labelledby="cart-summary">
                <h2 id="cart-summary">Özet</h2>
                <p><span>Ara Toplam</span><span><?= number_format($subtotal, 2, ',', '.') ?></span></p>
                <p><span>İndirim</span><span><?= number_format($discount, 2, ',', '.') ?></span></p>
                <p class="total"><span>Genel Toplam</span><span><?= number_format($total, 2, ',', '.') ?></span></p>
                <form method="post" action="/cart/coupon" class="coupon-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
                    <label for="coupon_code">Kupon</label>
                    <div class="coupon-input">
                        <input id="coupon_code" name="coupon_code" value="<?= htmlspecialchars($coupon['code'] ?? '') ?>" placeholder="Kod girin">
                        <button class="button-small" type="submit">Uygula</button>
                    </div>
                </form>
                <a class="button" href="/checkout">Ödemeye Geç</a>
            </aside>
        </div>
    <?php endif; ?>
</section>
