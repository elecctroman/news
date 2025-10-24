<section class="delivery-view">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php if ($payload['type'] === 'account'): ?>
        <p><strong>E-posta:</strong> <?= htmlspecialchars($payload['credentials']['email']) ?></p>
        <p><strong>Parola:</strong> <span class="copyable" data-copy="<?= htmlspecialchars($payload['credentials']['password']) ?>"><?= htmlspecialchars($payload['credentials']['password']) ?></span></p>
        <?php if (!empty($payload['credentials']['backup'])): ?>
            <p><strong>Not:</strong> <?= htmlspecialchars($payload['credentials']['backup']) ?></p>
        <?php endif; ?>
    <?php elseif ($payload['type'] === 'key'): ?>
        <p><strong>E-PIN:</strong> <span class="copyable" data-copy="<?= htmlspecialchars($payload['key']) ?>"><?= htmlspecialchars($payload['key']) ?></span></p>
    <?php else: ?>
        <p>Teslimat bilgisi bulunamadı.</p>
    <?php endif; ?>
    <p class="muted">Bu bağlantı tek kullanımlıktır.</p>
</section>
