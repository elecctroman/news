<section class="admin-orders">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php if (!empty($messages)): ?>
        <div class="alert alert-success" role="status">
            <ul><?php foreach ($messages as $message): ?><li><?= htmlspecialchars($message) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error" role="alert">
            <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <table class="table">
        <thead>
        <tr>
            <th>No</th>
            <th>Müşteri</th>
            <th>Durum</th>
            <th>Tutar</th>
            <th>İşlemler</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td>#<?= htmlspecialchars($order['id']) ?></td>
                <td><?= htmlspecialchars($order['email']) ?></td>
                <td><?= htmlspecialchars($order['status']) ?></td>
                <td><?= number_format((float) $order['total_amount'], 2, ',', '.') ?> <?= htmlspecialchars($order['currency']) ?></td>
                <td>
                    <form method="post" action="/admin/orders" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                        <input type="hidden" name="action" value="update_status">
                        <select name="status">
                            <?php foreach (['pending','payment_pending','paid','delivered','refunded','cancelled'] as $status): ?>
                                <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$status)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="button-small" type="submit">Güncelle</button>
                    </form>
                    <form method="post" action="/admin/orders" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                        <input type="hidden" name="action" value="resend_delivery">
                        <button class="button-link" type="submit">Teslimatı Yenile</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
