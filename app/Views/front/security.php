<section class="security-settings">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php if ($message): ?>
        <div class="alert alert-success" role="status"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert alert-error" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <section class="twofa">
        <h2>İki Adımlı Doğrulama</h2>
        <?php if (!empty($user['twofa_secret']) && (int) $user['twofa_enabled'] === 1): ?>
            <p>Hesabınız için iki adımlı doğrulama aktiftir.</p>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
                <input type="hidden" name="action" value="disable">
                <button class="button-secondary" type="submit">2FA'yı Devre Dışı Bırak</button>
            </form>
        <?php else: ?>
            <p>Hesabınızı korumak için doğrulama kodlarını etkinleştirin.</p>
            <div class="qr-box">
                <p>Authenticator uygulamanıza aşağıdaki URI ile ekleyin:</p>
                <code><?= htmlspecialchars($qr_uri) ?></code>
                <p>Veya gizli anahtar: <strong><?= htmlspecialchars($secret) ?></strong></p>
            </div>
            <form method="post" class="twofa-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
                <input type="hidden" name="action" value="enable">
                <label for="code">Doğrulama kodu</label>
                <input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required>
                <button class="button" type="submit">Etkinleştir</button>
            </form>
        <?php endif; ?>
    </section>

    <?php if ($recovery_codes): ?>
        <section class="recovery-codes" aria-live="polite">
            <h2>Kurtarma Kodları</h2>
            <p>Bu kodları güvenli bir yerde saklayın. Her biri bir kez kullanılabilir.</p>
            <ul>
                <?php foreach ($recovery_codes as $code): ?>
                    <li><code><?= htmlspecialchars($code) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</section>
