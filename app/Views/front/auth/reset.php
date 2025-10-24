<section class="auth-card">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php if ($status): ?>
        <div class="alert alert-success" role="status"><?= htmlspecialchars($status) ?></div>
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
    <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <label for="password">Yeni Parola</label>
        <input id="password" name="password" type="password" required>
        <button class="button" type="submit">Parolayı Güncelle</button>
    </form>
</section>
