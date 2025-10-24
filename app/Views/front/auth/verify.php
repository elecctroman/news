<section class="auth-card">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php if ($verified): ?>
        <div class="alert alert-success" role="status">E-posta adresiniz doğrulandı. <a href="/login">Giriş yapabilirsiniz.</a></div>
    <?php else: ?>
        <div class="alert alert-error" role="alert">Doğrulama bağlantısı geçersiz veya süresi dolmuş olabilir.</div>
    <?php endif; ?>
</section>
