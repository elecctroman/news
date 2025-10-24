<section class="ticket-create">
    <h1><?= htmlspecialchars($title) ?></h1>
    <p class="text-muted">Sipariş, hesap ya da ödeme konusundaki sorularınız için destek isteği açın.</p>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">Talebiniz alındı. Yanıt aldığınızda e-posta ile bilgilendirileceksiniz.</div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="ticket-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf->token()) ?>">
        <label for="subject">Konu
            <input id="subject" name="subject" required maxlength="190" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
        </label>
        <label for="order_id">İlgili Sipariş
            <select id="order_id" name="order_id">
                <option value="">Sipariş seçin (isteğe bağlı)</option>
                <?php foreach ($orders as $order): ?>
                    <option value="<?= (int) $order['id'] ?>" <?= isset($_POST['order_id']) && (int) $_POST['order_id'] === (int) $order['id'] ? 'selected' : '' ?>>#<?= (int) $order['id'] ?> - <?= number_format((float) $order['total_amount'], 2, ',', '.') ?> <?= htmlspecialchars($order['currency']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label for="priority">Öncelik
            <select id="priority" name="priority">
                <option value="normal" <?= (($_POST['priority'] ?? '') === 'normal') ? 'selected' : '' ?>>Normal</option>
                <option value="urgent" <?= (($_POST['priority'] ?? '') === 'urgent') ? 'selected' : '' ?>>Acil</option>
            </select>
        </label>
        <label for="category">Kategori
            <select id="category" name="category">
                <?php foreach ($categories as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= (($_POST['category'] ?? 'support') === $key) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label for="message">Mesaj
            <textarea id="message" name="message" required rows="6"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        </label>
        <label for="attachments" class="file-label">Ek Dosyalar (max 3 adet)
            <input type="file" id="attachments" name="attachments[]" multiple accept=".png,.jpg,.jpeg,.pdf,.txt,.zip">
        </label>
        <p class="text-muted" role="note">Her dosya en fazla 2 MB olmalıdır. Zararlı uzantılar otomatik olarak reddedilir.</p>
        <button class="button" type="submit">Talep Oluştur</button>
    </form>
</section>
