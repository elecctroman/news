<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf->token()) ?>">
    <title><?= htmlspecialchars($title ?? 'Mağaza') ?></title>
    <?php if (!empty($metaDescription)): ?>
        <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/app.min.css">
</head>
<body>
<a class="skip-link" href="#icerik">İçeriğe geç</a>
<?php $authView = $GLOBALS['auth'] ?? null; $currentUser = $authView?->user(); ?>
<header class="site-header">
    <div class="logo-area">
        <a class="brand" href="/">🎮 Dijital Mağaza</a>
        <form class="search" action="/katalog" method="get" role="search">
            <label for="site-search" class="sr-only">Ürün ara</label>
            <input id="site-search" name="q" type="search" placeholder="Ürün veya anahtar ara" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <button type="submit">Ara</button>
        </form>
    </div>
    <nav class="primary-nav" aria-label="Ana menü">
        <a href="/katalog">Katalog</a>
        <a href="/account">Hesabım</a>
        <?php if ($currentUser): ?>
            <span class="user-chip" aria-label="Aktif kullanıcı"><?= htmlspecialchars($currentUser['email']) ?></span>
            <a href="/logout">Çıkış</a>
        <?php else: ?>
            <a href="/login">Giriş</a>
            <a href="/register">Kayıt</a>
        <?php endif; ?>
    </nav>
</header>
<main id="icerik" tabindex="-1">
    <?= $content ?>
</main>
<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> Dijital Mağaza İskeleti</p>
</footer>
<script src="/assets/js/app.min.js" defer></script>
</body>
</html>
