<section class="auth-card">
    <h1><?= htmlspecialchars($title) ?></h1>
    <p>Güvenliğe devam etmek için doğrulama kodunuzu girin.</p>
    <?php if ($errors): ?>
        <div class="alert alert-error" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
        <label for="code">6 Haneli Kod</label>
        <input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required>
        <button class="button" type="submit">Doğrula</button>
    </form>
</section>
