<section class="account-overview">
    <h1><?= htmlspecialchars($title) ?></h1>
    <div class="account-grid">
        <div class="account-card" aria-labelledby="account-info">
            <h2 id="account-info">Profil Bilgileri</h2>
            <dl>
                <div>
                    <dt>E-posta</dt>
                    <dd><?= htmlspecialchars($user['email'] ?? '') ?></dd>
                </div>
                <div>
                    <dt>Rol</dt>
                    <dd><?= htmlspecialchars($user['role'] ?? '-') ?></dd>
                </div>
                <div>
                    <dt>E-posta Doğrulaması</dt>
                    <dd><?= !empty($user['email_verified_at']) ? 'Doğrulandı' : 'Bekliyor' ?></dd>
                </div>
                <div>
                    <dt>İki Adımlı Doğrulama</dt>
                    <dd><?= !empty($user['twofa_secret']) && (int) $user['twofa_enabled'] === 1 ? 'Aktif' : 'Kapalı' ?></dd>
                </div>
            </dl>
            <div class="account-actions">
                <a class="button" href="/account/security">Güvenlik Ayarları</a>
                <a class="button-secondary" href="/katalog">Alışverişe Başla</a>
            </div>
        </div>
        <div class="account-card" aria-labelledby="recent-orders">
            <h2 id="recent-orders">Son Siparişler</h2>
            <?php if (empty($orders)): ?>
                <p>Henüz siparişiniz bulunmuyor.</p>
            <?php else: ?>
                <ul class="order-list">
                    <?php foreach ($orders as $order): ?>
                        <li>
                            <span>#<?= htmlspecialchars($order['id']) ?></span>
                            <span><?= number_format((float) $order['total_amount'], 2, ',', '.') ?> <?= htmlspecialchars($order['currency']) ?></span>
                            <span class="status status-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span>
                            <a class="button-small" href="/account/orders/<?= (int) $order['id'] ?>">Detay</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a class="button-link" href="/account/orders">Tümünü Gör</a>
            <?php endif; ?>
        </div>
        <div class="account-card" aria-labelledby="address-book">
            <h2 id="address-book">Adres Defteri</h2>
            <?php if (empty($addresses)): ?>
                <p>Kayıtlı adres bulunamadı. Ödeme sırasında ekleyebilirsiniz.</p>
            <?php else: ?>
                <ul class="address-list">
                    <?php foreach ($addresses as $address): ?>
                        <li>
                            <strong><?= htmlspecialchars($address['full_name']) ?></strong><br>
                            <?= htmlspecialchars($address['line1']) ?> <?= htmlspecialchars($address['line2'] ?? '') ?><br>
                            <?= htmlspecialchars($address['city']) ?> / <?= htmlspecialchars($address['country']) ?><br>
                            <?php if (!empty($address['tax_number'])): ?>Vergi No: <?= htmlspecialchars($address['tax_number']) ?><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="account-card" aria-labelledby="support-center">
            <h2 id="support-center">Destek Merkezi</h2>
            <p class="text-muted">Siparişleriniz ve hesaplarınız için yardım alın.</p>
            <a class="button" href="/account/tickets/new">Yeni Destek Talebi</a>
            <a class="button-secondary" href="/account/tickets">Biletlerimi Görüntüle</a>
        </div>
    </div>
</section>
