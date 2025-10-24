<section class="order-success">
    <h1><?= htmlspecialchars($title) ?></h1>
    <p>Siparişiniz başarıyla tamamlandı. Dijital içerikleriniz hesabınıza tanımlandı.</p>
    <?php if (!empty($order)): ?>
        <h2>#<?= htmlspecialchars($order['id']) ?> özet</h2>
        <ul>
            <?php foreach ($order['items'] as $item): ?>
                <li><?= htmlspecialchars($item['product_name']) ?> — <?= (int) $item['quantity'] ?> adet</li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <a class="button" href="/account/orders">Siparişlerime Git</a>
</section>
