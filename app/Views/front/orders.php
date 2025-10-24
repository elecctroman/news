<section class="orders-page">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php if (empty($orders)): ?>
        <p>Sipariş geçmişiniz boş.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th scope="col">Sipariş No</th>
                <th scope="col">Tarih</th>
                <th scope="col">Tutar</th>
                <th scope="col">Durum</th>
                <th scope="col">İşlem</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td data-title="Sipariş No">#<?= htmlspecialchars($order['id']) ?></td>
                    <td data-title="Tarih"><?= htmlspecialchars($order['created_at']) ?></td>
                    <td data-title="Tutar"><?= number_format((float) $order['total_amount'], 2, ',', '.') ?> <?= htmlspecialchars($order['currency']) ?></td>
                    <td data-title="Durum" class="status status-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></td>
                    <td>
                        <a class="button-small" href="/account/orders/<?= (int) $order['id'] ?>">Detay</a>
                        <a class="button-link" href="/account/orders/<?= (int) $order['id'] ?>/invoice">PDF</a>
                        <a class="button-link" href="/account/orders/<?= (int) $order['id'] ?>/invoice.csv">CSV</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
