<section class="order-detail">
    <h1><?= htmlspecialchars($title) ?></h1>
    <header class="order-detail__header">
        <div>
            <p><strong>Sipariş No:</strong> #<?= htmlspecialchars($order['id']) ?></p>
            <p><strong>Tarih:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
        </div>
        <div>
            <p><strong>Durum:</strong> <span class="status status-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></p>
            <p><strong>Toplam:</strong> <?= number_format((float) $order['total_amount'], 2, ',', '.') ?> <?= htmlspecialchars($order['currency']) ?></p>
        </div>
        <nav class="order-detail__actions" aria-label="Fatura İşlemleri">
            <a class="button-secondary" href="/account/orders/<?= (int) $order['id'] ?>/invoice">PDF Fatura</a>
            <a class="button-secondary" href="/account/orders/<?= (int) $order['id'] ?>/invoice.csv">CSV Fatura</a>
        </nav>
    </header>

    <?php if (!empty($messages)): ?>
        <div class="alert alert-info" role="status">
            <ul>
                <?php foreach ($messages as $message): ?>
                    <li><?= htmlspecialchars($message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <section aria-labelledby="order-items">
        <h2 id="order-items">Sipariş Kalemleri</h2>
        <div class="order-items">
            <?php foreach ($order['items'] as $item): ?>
                <article class="order-item-card">
                    <header>
                        <h3><?= htmlspecialchars($item['product_name']) ?></h3>
                        <p><?= (int) $item['quantity'] ?> adet • <?= number_format((float) $item['unit_price'], 2, ',', '.') ?> <?= htmlspecialchars($order['currency']) ?></p>
                    </header>
                    <?php if (isset($deliveries[$item['id']])): ?>
                        <?php $delivery = $deliveries[$item['id']]['delivery']; $payload = $deliveries[$item['id']]['payload']; ?>
                        <?php if ($payload['type'] === 'key'): ?>
                            <div class="delivery-block">
                                <p><strong>E-PIN:</strong> <span class="copyable" data-copy="<?= htmlspecialchars($payload['key']) ?>"><?= htmlspecialchars($payload['key']) ?></span></p>
                            </div>
                        <?php elseif ($payload['type'] === 'account'): ?>
                            <div class="delivery-block">
                                <p><strong>E-posta:</strong> <?= htmlspecialchars($payload['credentials']['email']) ?></p>
                                <p><strong>Parola:</strong> <span class="copyable" data-copy="<?= htmlspecialchars($payload['credentials']['password']) ?>"><?= htmlspecialchars($payload['credentials']['password']) ?></span></p>
                                <?php if (!empty($payload['credentials']['backup'])): ?>
                                    <p><strong>Not:</strong> <?= htmlspecialchars($payload['credentials']['backup']) ?></p>
                                <?php endif; ?>
                                <form method="post" action="/account/orders/link" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
                                    <input type="hidden" name="order_item_id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="button-small">Tek Kullanımlık Bağlantı Üret</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="muted">Teslimat hazırlanıyor.</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</section>
