<section class="admin-section">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php if ($messages): ?>
        <div class="alert alert-success" role="status">
            <ul>
                <?php foreach ($messages as $message): ?>
                    <li><?= htmlspecialchars($message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
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

    <div class="admin-panels">
        <form class="panel" method="post">
            <h2>Yeni Ürün</h2>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
            <input type="hidden" name="action" value="create_product">
            <label>Ürün Adı
                <input name="name" required>
            </label>
            <label>Slug
                <input name="slug" placeholder="otomatik üretilecek">
            </label>
            <label>Kısa Açıklama
                <input name="short_description">
            </label>
            <label>Açıklama
                <textarea name="description" rows="3"></textarea>
            </label>
            <label>Fiyat
                <input name="price" type="number" step="0.01" required>
            </label>
            <label>Para Birimi
                <input name="currency" value="TRY">
            </label>
            <label>Tür
                <select name="type">
                    <option value="key">E-PIN / Lisans</option>
                    <option value="account">Hesap</option>
                    <option value="bundle">Paket</option>
                </select>
            </label>
            <label class="checkbox">
                <input type="checkbox" name="is_featured"> Öne Çıkan
            </label>
            <label class="checkbox">
                <input type="checkbox" name="is_new"> Yeni
            </label>
            <fieldset>
                <legend>Kategoriler</legend>
                <?php foreach ($categories as $category): ?>
                    <label class="checkbox">
                        <input type="checkbox" name="categories[]" value="<?= (int) $category['id'] ?>">
                        <?= htmlspecialchars($category['name']) ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <fieldset>
                <legend>Etiketler</legend>
                <?php foreach ($tags as $tag): ?>
                    <label class="checkbox">
                        <input type="checkbox" name="tags[]" value="<?= (int) $tag['id'] ?>">
                        <?= htmlspecialchars($tag['name']) ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <button class="button" type="submit">Ürünü Kaydet</button>
        </form>

        <form class="panel" method="post">
            <h2>Varyant Ekle</h2>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
            <input type="hidden" name="action" value="add_variant">
            <label>Ürün
                <select name="product_id" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int) $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Varyant Adı
                <input name="variant_name" value="Standart">
            </label>
            <label>Platform
                <input name="platform">
            </label>
            <label>Bölge
                <input name="region">
            </label>
            <label>Süre (gün)
                <input name="duration_days" type="number" min="0">
            </label>
            <label>Fiyat Farkı
                <input name="price_adjustment" type="number" step="0.01">
            </label>
            <label>Stok
                <input name="stock" type="number" min="0">
            </label>
            <button class="button" type="submit">Varyant Kaydet</button>
        </form>

        <form class="panel" method="post">
            <h2>E-PIN Toplu İçe Aktar</h2>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
            <input type="hidden" name="action" value="import_keys">
            <label>Ürün
                <select name="product_id" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int) $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Varyant (opsiyonel)
                <input name="variant_id" type="number" min="0" placeholder="ID">
            </label>
            <label>CSV İçeriği (her satır bir anahtar)
                <textarea name="csv_data" rows="5" placeholder="XXXX-YYYY-1234-5678"></textarea>
            </label>
            <button class="button" type="submit">Anahtarları Ekle</button>
        </form>

        <form class="panel" method="post">
            <h2>Hesap Toplu İçe Aktar</h2>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
            <input type="hidden" name="action" value="import_accounts">
            <label>Ürün
                <select name="product_id" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int) $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Varyant (opsiyonel)
                <input name="variant_id" type="number" min="0" placeholder="ID">
            </label>
            <label>CSV İçeriği (email,password,not)
                <textarea name="csv_data" rows="5" placeholder="mail@example.com,Parola123,Not"></textarea>
            </label>
            <button class="button" type="submit">Hesapları Ekle</button>
        </form>
    </div>

    <table class="table responsive">
        <thead>
            <tr>
                <th>Ürün</th>
                <th>Tür</th>
                <th>Fiyat</th>
                <th>Kategoriler</th>
                <th>Durum</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= htmlspecialchars($product['type']) ?></td>
                    <td><?= number_format((float) $product['base_price'], 2, ',', '.') ?> <?= htmlspecialchars($product['currency']) ?></td>
                    <td><?= htmlspecialchars($product['categories'] ?? '') ?></td>
                    <td><?= htmlspecialchars($product['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
