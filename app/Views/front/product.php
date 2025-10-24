<article class="product-detail" itemscope itemtype="https://schema.org/Product">
    <header class="product-hero">
        <div>
            <h1 itemprop="name"><?= htmlspecialchars($product['name']) ?></h1>
            <p class="product-summary"><?= htmlspecialchars($product['short_description'] ?? '') ?></p>
            <div class="product-tags">
                <?php foreach ($product['categories'] as $category): ?>
                    <span class="chip" itemprop="category"><?= htmlspecialchars($category['name']) ?></span>
                <?php endforeach; ?>
                <?php foreach ($product['tags'] as $tag): ?>
                    <span class="chip chip-alt">#<?= htmlspecialchars($tag['name']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="product-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
            <meta itemprop="priceCurrency" content="<?= htmlspecialchars($product['currency']) ?>">
            <span class="price" itemprop="price"><?= number_format((float) $product['base_price'], 2, ',', '.') ?></span>
            <span class="currency"><?= htmlspecialchars($product['currency']) ?></span>
            <link itemprop="availability" href="https://schema.org/InStock">
        </div>
    </header>

    <div class="product-body">
        <section class="product-description" itemprop="description">
            <?= $product['description'] ?>
        </section>
        <section class="product-variants" aria-labelledby="varyant-baslik">
            <h2 id="varyant-baslik">Satın Alma</h2>
            <form class="variant-form" action="/cart/add" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <?php if (!empty($product['variants'])): ?>
                    <label for="variant">Varyant</label>
                    <select id="variant" name="variant_id">
                        <?php foreach ($product['variants'] as $variant): ?>
                            <option value="<?= (int) $variant['id'] ?>">
                                <?= htmlspecialchars($variant['name']) ?>
                                <?php if (!empty($variant['region'])): ?>
                                    (<?= htmlspecialchars($variant['region']) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <label for="qty">Adet</label>
                <input id="qty" name="quantity" type="number" min="1" value="1">
                <button class="button" type="submit">Sepete Ekle</button>
            </form>
        </section>
    </div>

    <script type="application/ld+json">
        <?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
</article>
