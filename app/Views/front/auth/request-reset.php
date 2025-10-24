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
        <label for="email">E-posta</label>
        <input id="email" name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <button class="button" type="submit">Sıfırlama Bağlantısı Gönder</button>
    </form>
</section>
