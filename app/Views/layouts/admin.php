<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf->token()) ?>">
    <title><?= htmlspecialchars($title ?? 'Yönetim') ?></title>
    <link rel="stylesheet" href="/assets/css/app.min.css">
</head>
<body class="admin">
<header class="admin-header">
    <div class="admin-brand">
        <a href="/admin">Yönetim Paneli</a>
    </div>
    <nav aria-label="Yönetim menüsü">
        <a href="/admin">Pano</a>
        <a href="/admin/products">Ürünler</a>
        <a href="/admin/orders">Siparişler</a>
        <a href="/admin/tickets">Destek</a>
        <a href="/admin/reports">Raporlar</a>
        <a href="/admin/maintenance">Bakım</a>
        <a href="/">Siteye Git</a>
    </nav>
</header>
<main class="admin-content">
    <?= $content ?>
</main>
<footer class="admin-footer">
    Yönetim Paneli İskeleti
</footer>
<script src="/assets/js/app.min.js" defer></script>
</body>
</html>
