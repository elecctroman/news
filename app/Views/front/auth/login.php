<section class="auth-card">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php if ($errors): ?>
        <div class="alert alert-error" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
        <label for="email">E-posta</label>
        <input id="email" name="email" type="email" autocomplete="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <label for="password">Parola</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <button class="button" type="submit">Giriş Yap</button>
    </form>
    <p class="muted"><a href="/forgot-password">Parolanızı mı unuttunuz?</a></p>
</section>
