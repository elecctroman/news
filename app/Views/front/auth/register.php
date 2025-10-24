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
        <label for="name">Ad Soyad</label>
        <input id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        <label for="email">E-posta</label>
        <input id="email" name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <label for="password">Parola</label>
        <input id="password" name="password" type="password" required>
        <button class="button" type="submit">Kayıt Ol</button>
    </form>
    <p class="muted">Zaten hesabınız var mı? <a href="/login">Giriş yapın</a>.</p>
</section>
