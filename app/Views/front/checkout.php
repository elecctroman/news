<section class="checkout">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="checkout-grid">
        <section class="checkout-summary" aria-labelledby="summary-title">
            <h2 id="summary-title">Sipariş Özeti</h2>
            <ul>
                <?php foreach ($cart as $item): ?>
                    <li>
                        <span><?= htmlspecialchars($item['name']) ?></span>
                        <span><?= (int) $item['qty'] ?> × <?= number_format((float) $item['unit_price'], 2, ',', '.') ?> <?= htmlspecialchars($item['currency']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="totals">
                <p><span>Ara Toplam</span><span><?= number_format($subtotal, 2, ',', '.') ?> <?= htmlspecialchars($currency) ?></span></p>
                <p><span>İndirim</span><span>-<?= number_format($discount, 2, ',', '.') ?></span></p>
                <p class="total"><span>Genel Toplam</span><span><?= number_format($total, 2, ',', '.') ?> <?= htmlspecialchars($currency) ?></span></p>
            </div>
        </section>

        <form class="checkout-form" method="post" aria-labelledby="payment-title">
            <h2 id="payment-title">Fatura ve Ödeme</h2>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
            <label for="billing_name">Ad Soyad</label>
            <input id="billing_name" name="billing_name" required autocomplete="name">

            <label for="billing_address">Adres Satırı</label>
            <input id="billing_address" name="billing_address" required autocomplete="address-line1">

            <label for="billing_line2">Adres 2</label>
            <input id="billing_line2" name="billing_line2" autocomplete="address-line2">

            <label for="billing_city">Şehir</label>
            <input id="billing_city" name="billing_city" required autocomplete="address-level2">

            <label for="billing_country">Ülke</label>
            <input id="billing_country" name="billing_country" required autocomplete="country-name">

            <label for="billing_tax">Vergi Numarası</label>
            <input id="billing_tax" name="billing_tax" autocomplete="off">

            <label for="payment_method">Ödeme Yöntemi</label>
            <select id="payment_method" name="payment_method">
                <option value="mock">Mock Test Gateway</option>
                <option value="bank3d">Banka / 3D Secure</option>
                <option value="wallet">E-Cüzdan / QR</option>
            </select>

            <label for="simulate">Senaryo</label>
            <select id="simulate" name="simulate">
                <option value="success">Başarılı</option>
                <option value="failed">Başarısız</option>
            </select>

            <button class="button" type="submit">Ödemeyi Tamamla</button>
        </form>
    </div>
</section>
