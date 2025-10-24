<section class="hero">
    <div>
        <h1><?= htmlspecialchars($title) ?></h1>
        <p>Dijital anahtar, hesap ve aboneliklerinizi güvenle yönetin.</p>
        <a class="button" href="/katalog">Kataloğu Keşfet</a>
    </div>
</section>

<section class="category-strip" aria-labelledby="kategori-baslik">
    <h2 id="kategori-baslik">Kategoriler</h2>
    <div class="chip-list" role="list">
        <?php foreach ($categories as $category): ?>
            <a role="listitem" class="chip" href="/katalog?kategori=<?= urlencode($category['slug']) ?>"><?= htmlspecialchars($category['name']) ?></a>
        <?php endforeach; ?>
    </div>
</section>

<section aria-labelledby="onecikan-baslik">
    <div class="section-head">
        <h2 id="onecikan-baslik">Öne Çıkan Ürünler</h2>
        <a class="link" href="/katalog">Tümünü Gör</a>
    </div>
    <div class="product-grid" role="list">
        <?php foreach ($featured as $item): ?>
            <article role="listitem" class="product-card">
                <h3><a href="/urun/<?= urlencode($item['slug']) ?>"><?= htmlspecialchars($item['name']) ?></a></h3>
                <p><?= htmlspecialchars($item['short_description'] ?? '') ?></p>
                <div class="product-meta">
                    <span class="price"><?= number_format((float) $item['base_price'], 2, ',', '.') ?> <?= htmlspecialchars($item['currency']) ?></span>
                    <?php if (!empty($item['type']) && $item['type'] === 'key'): ?>
                        <span class="badge">E-PIN</span>
                    <?php elseif (!empty($item['type']) && $item['type'] === 'account'): ?>
                        <span class="badge badge-alt">Hesap</span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
