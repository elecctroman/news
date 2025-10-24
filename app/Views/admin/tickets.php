<section>
    <h1><?= htmlspecialchars($title) ?></h1>
    <form method="get" class="filter-bar" aria-label="Filtrele">
        <label for="status">Durum Filtrele
            <select id="status" name="status" onchange="this.form.submit()">
                <option value="">Tümü</option>
                <?php foreach (['open','waiting_admin','waiting_customer','dispute','resolved','closed'] as $state): ?>
                    <option value="<?= htmlspecialchars($state) ?>" <?= ($status ?? '') === $state ? 'selected' : '' ?>><?= htmlspecialchars(strtoupper($state)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <noscript><button class="button-small" type="submit">Filtrele</button></noscript>
    </form>
    <?php $categoryLabels = ['support' => 'Genel', 'order' => 'Sipariş', 'payment' => 'Ödeme', 'dispute' => 'Uyuşmazlık']; ?>
    <table class="table">
        <thead><tr><th>ID</th><th>Konu</th><th>Kullanıcı</th><th>Öncelik</th><th>Durum</th><th>Kategori</th><th>Etiketler</th><th>Güncellendi</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $ticket): ?>
            <tr>
                <td>#<?= htmlspecialchars($ticket['id']) ?></td>
                <td><?= htmlspecialchars($ticket['subject']) ?></td>
                <td><?= htmlspecialchars($ticket['email'] ?? '-') ?></td>
                <td><?= htmlspecialchars($ticket['priority']) ?></td>
                <td><span class="status status-<?= htmlspecialchars($ticket['status']) ?>"><?= htmlspecialchars($ticket['status']) ?></span></td>
                <td><?= htmlspecialchars($categoryLabels[$ticket['category'] ?? 'support'] ?? ($ticket['category'] ?? 'support')) ?></td>
                <td><?= htmlspecialchars($ticket['tag_list'] ?? '') ?></td>
                <td><?= htmlspecialchars($ticket['updated_at']) ?></td>
                <td><a class="button-small" href="/admin/tickets/<?= (int) $ticket['id'] ?>">Görüntüle</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
