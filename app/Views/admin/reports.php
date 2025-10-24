<section class="reports">
    <h1><?= htmlspecialchars($title) ?></h1>
    <form method="get" class="filter-bar" aria-label="Tarih aralığı">
        <label for="from">Başlangıç
            <input type="date" id="from" name="from" value="<?= htmlspecialchars($from) ?>">
        </label>
        <label for="to">Bitiş
            <input type="date" id="to" name="to" value="<?= htmlspecialchars($to) ?>">
        </label>
        <button class="button-small" type="submit">Uygula</button>
        <a class="button-small" href="/admin/reports/export?from=<?= htmlspecialchars($from) ?>&amp;to=<?= htmlspecialchars($to) ?>">CSV Dışa Aktar</a>
    </form>

    <div class="grid grid-cols-4">
        <article class="card"><h2>Brüt Satış</h2><p><?= number_format((float) $summary['gross'], 2, ',', '.') ?></p></article>
        <article class="card"><h2>Net Satış</h2><p><?= number_format((float) $summary['net'], 2, ',', '.') ?></p></article>
        <article class="card"><h2>Sipariş</h2><p><?= (int) $summary['orders'] ?></p></article>
        <article class="card"><h2>İade</h2><p><?= (int) $summary['refunds'] ?></p></article>
    </div>

    <canvas class="card chart" width="700" height="260" data-chart data-series='<?= json_encode($chart['series']) ?>'></canvas>

    <section class="card">
        <h2>En Çok Satan Ürünler</h2>
        <table class="table">
            <thead><tr><th>Ürün</th><th>Adet</th><th>Gelir</th></tr></thead>
            <tbody>
            <?php foreach ($topProducts as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['product']) ?></td>
                    <td><?= (int) $product['total'] ?></td>
                    <td><?= number_format((float) $product['revenue'], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($topProducts)): ?>
                <tr><td colspan="3" class="text-muted">Veri bulunamadı.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2>Siparişler</h2>
        <table class="table">
            <thead><tr><th>ID</th><th>Kullanıcı</th><th>Durum</th><th>Tutar</th><th>İndirim</th><th>Para Birimi</th><th>Tarih</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= (int) $order['id'] ?></td>
                    <td><?= (int) $order['user_id'] ?></td>
                    <td><?= htmlspecialchars($order['status']) ?></td>
                    <td><?= number_format((float) $order['total_amount'], 2, ',', '.') ?></td>
                    <td><?= number_format((float) $order['discount_amount'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($order['currency']) ?></td>
                    <td><?= htmlspecialchars($order['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
                <tr><td colspan="7" class="text-muted">Seçilen aralıkta sipariş bulunmuyor.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <nav class="pagination" aria-label="Sayfalama">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a class="pagination__item <?= $p === $page ? 'is-active' : '' ?>" href="?from=<?= htmlspecialchars($from) ?>&amp;to=<?= htmlspecialchars($to) ?>&amp;page=<?= $p ?>"><?= $p ?></a>
            <?php endfor; ?>
        </nav>
    </section>
</section>
