<section class="admin-dashboard">
    <div class="admin-panels">
        <article class="card">
            <h2>Bugünkü Satış</h2>
            <p><?= (int) ($stats['sales_today'] ?? 0) ?> sipariş</p>
        </article>
        <article class="card">
            <h2>Bekleyen Destek</h2>
            <p><?= (int) ($stats['pending_tickets'] ?? 0) ?> bilet</p>
        </article>
        <article class="card">
            <h2>Düşük Stok Alarmı</h2>
            <p><?= (int) ($stats['low_stock'] ?? 0) ?> ürün</p>
        </article>
    </div>
    <div class="card">
        <h2>Son 7 Gün Satış Trendleri</h2>
        <canvas width="480" height="200" data-chart data-points='<?= json_encode($chartPoints, JSON_THROW_ON_ERROR) ?>'></canvas>
    </div>
</section>
