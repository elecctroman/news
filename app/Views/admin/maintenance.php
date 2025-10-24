<section class="maintenance">
    <h1><?= htmlspecialchars($title) ?></h1>
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
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <section class="card">
        <h2>Bakım Modu</h2>
        <form method="post" class="form-grid">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf->token()) ?>">
            <input type="hidden" name="action" value="maintenance">
            <label for="maintenance_mode">Durum
                <select id="maintenance_mode" name="maintenance_mode">
                    <option value="off" <?= (($settings['maintenance_mode'] ?? 'off') === 'off') ? 'selected' : '' ?>>Kapalı</option>
                    <option value="on" <?= (($settings['maintenance_mode'] ?? 'off') === 'on') ? 'selected' : '' ?>>Açık</option>
                </select>
            </label>
            <label for="maintenance_whitelist">IP Beyaz Liste (virgülle ayrılmış)
                <textarea id="maintenance_whitelist" name="maintenance_whitelist" rows="2" placeholder="127.0.0.1,192.168.1.1"><?= htmlspecialchars($settings['maintenance_whitelist'] ?? '') ?></textarea>
            </label>
            <button class="button" type="submit">Kaydet</button>
        </form>
    </section>

    <section class="card">
        <h2>Veritabanı Yedeği</h2>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf->token()) ?>">
            <input type="hidden" name="action" value="backup">
            <p class="text-muted">Bu işlem mevcut veritabanının SQL dökümünü storage/backups klasörüne kaydeder.</p>
            <button class="button" type="submit">Yedek Oluştur</button>
        </form>
    </section>

    <section class="card">
        <h2>Son Güvenlik Taraması</h2>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf->token()) ?>">
            <input type="hidden" name="action" value="scan">
            <button class="button" type="submit">Taramayı Çalıştır</button>
        </form>
        <?php if (!empty($scanResults)): ?>
            <ul class="scan-results" role="list">
                <?php foreach ($scanResults as $result): ?>
                    <li class="scan-results__item scan-results__item--<?= htmlspecialchars($result['status']) ?>">
                        <strong><?= htmlspecialchars($result['name']) ?>:</strong> <?= htmlspecialchars($result['message']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</section>
