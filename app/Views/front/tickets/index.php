<section class="ticket-list">
    <h1><?= htmlspecialchars($title) ?></h1>
    <p class="text-muted">Siparişlerinizle ilgili destek taleplerinizi buradan takip edebilirsiniz.</p>
    <div class="ticket-actions">
        <a class="button" href="/account/tickets/new">Yeni Bilet Oluştur</a>
    </div>
    <?php if (empty($tickets)): ?>
        <div class="alert alert-info">Henüz destek talebiniz yok.</div>
    <?php else: ?>
        <?php $categoryLabels = ['support' => 'Genel', 'order' => 'Sipariş', 'payment' => 'Ödeme', 'dispute' => 'Uyuşmazlık']; ?>
        <table class="table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Konu</th>
                <th>Durum</th>
                <th>Öncelik</th>
                <th>Kategori</th>
                <th>Etiketler</th>
                <th>Güncel</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td>#<?= (int) $ticket['id'] ?></td>
                    <td><?= htmlspecialchars($ticket['subject']) ?></td>
                    <td><span class="status status-<?= htmlspecialchars($ticket['status']) ?>"><?= htmlspecialchars($ticket['status']) ?></span></td>
                    <td><?= htmlspecialchars($ticket['priority']) ?></td>
                    <td><?= htmlspecialchars($categoryLabels[$ticket['category'] ?? 'support'] ?? ($ticket['category'] ?? 'support')) ?></td>
                    <td><?= htmlspecialchars($ticket['tag_list'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ticket['updated_at']) ?></td>
                    <td><a class="button-small" href="/account/tickets/<?= (int) $ticket['id'] ?>">Görüntüle</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
