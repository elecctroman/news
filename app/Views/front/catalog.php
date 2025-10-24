<section class="catalog" aria-labelledby="catalog-title">
    <div class="catalog-header">
        <h1 id="catalog-title"><?= htmlspecialchars($title) ?></h1>
        <form method="get" class="catalog-sort">
            <label for="sirala">Sırala</label>
            <select id="sirala" name="sirala" onchange="this.form.submit()">
                <option value="" <?= empty($filters['sort']) ? 'selected' : '' ?>>Varsayılan</option>
                <option value="price_asc" <?= ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' ?>>Fiyat Artan</option>
                <option value="price_desc" <?= ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>Fiyat Azalan</option>
            </select>
            <input type="hidden" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
            <input type="hidden" name="kategori" value="<?= htmlspecialchars($filters['category'] ?? '') ?>">
            <input type="hidden" name="etiket" value="<?= htmlspecialchars($filters['tag'] ?? '') ?>">
        </form>
    </div>
    <div class="catalog-body">
        <aside class="catalog-filter" aria-label="Filtreler">
            <form method="get" class="filter-form">
                <fieldset>
                    <legend>Kategori</legend>
                    <div class="filter-list">
                        <?php foreach ($categories as $category): ?>
                            <label>
                                <input type="radio" name="kategori" value="<?= htmlspecialchars($category['slug']) ?>" <?= ($filters['category'] ?? '') === $category['slug'] ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($category['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                        <label>
                            <input type="radio" name="kategori" value="" <?= empty($filters['category']) ? 'checked' : '' ?>>
                            <span>Tümü</span>
                        </label>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Etiket</legend>
                    <div class="filter-list">
                        <?php foreach ($tags as $tag): ?>
                            <label>
                                <input type="radio" name="etiket" value="<?= htmlspecialchars($tag['slug']) ?>" <?= ($filters['tag'] ?? '') === $tag['slug'] ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($tag['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                        <label>
                            <input type="radio" name="etiket" value="" <?= empty($filters['tag']) ? 'checked' : '' ?>>
                            <span>Tümü</span>
                        </label>
                    </div>
                </fieldset>
                <label class="filter-search">Anahtar kelime
                    <input name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="ör. Steam">
                </label>
                <button class="button" type="submit">Filtrele</button>
            </form>
        </aside>
        <div class="product-grid" role="list">
            <?php if (!$products): ?>
                <p>Seçtiğiniz kriterlere uygun ürün bulunamadı.</p>
            <?php endif; ?>
            <?php foreach ($products as $product): ?>
                <article class="product-card" role="listitem">
                    <h2><a href="/urun/<?= urlencode($product['slug']) ?>"><?= htmlspecialchars($product['name']) ?></a></h2>
                    <p><?= htmlspecialchars($product['short_description'] ?? '') ?></p>
                    <div class="product-meta">
                        <span class="price"><?= number_format((float) $product['base_price'], 2, ',', '.') ?> <?= htmlspecialchars($product['currency']) ?></span>
                        <span class="badge"><?= htmlspecialchars(strtoupper($product['type'])) ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
