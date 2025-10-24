<section class="ticket-detail">
    <header class="ticket-detail__header">
        <div>
            <h1><?= htmlspecialchars($title) ?></h1>
            <?php $categoryLabels = ['support' => 'Genel', 'order' => 'Sipariş', 'payment' => 'Ödeme', 'dispute' => 'Uyuşmazlık']; ?>
            <p class="text-muted">Durum: <span class="status status-<?= htmlspecialchars($ticket['status']) ?>"><?= htmlspecialchars($ticket['status']) ?></span> · Öncelik: <?= htmlspecialchars($ticket['priority']) ?> · Kategori: <?= htmlspecialchars($categoryLabels[$ticket['category']] ?? $ticket['category']) ?></p>
            <?php if (!empty($ticket['tags'])): ?>
                <p class="tag-list" role="list">
                    <?php foreach ($ticket['tags'] as $tag): ?>
                        <span class="tag" role="listitem">#<?= htmlspecialchars($tag['name']) ?></span>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
        </div>
        <form method="post" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf->token()) ?>">
            <input type="hidden" name="action" value="close">
            <?php if ($ticket['status'] !== 'closed'): ?>
                <button class="button-secondary" type="submit">Bileti Kapat</button>
            <?php else: ?>
                <span class="text-muted">Bilet kapatılmış.</span>
            <?php endif; ?>
        </form>
    </header>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">İşlem başarıyla tamamlandı.</div>
    <?php endif; ?>

    <section class="ticket-thread" aria-label="Destek mesajları">
        <?php foreach ($ticket['messages'] as $message): ?>
            <article class="ticket-message ticket-message--<?= htmlspecialchars($message['sender_type']) ?>">
                <header>
                    <strong><?= htmlspecialchars($message['sender_type'] === 'admin' ? 'Destek Ekibi' : 'Siz') ?></strong>
                    <span class="text-muted"><?= htmlspecialchars($message['created_at']) ?></span>
                </header>
                <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                <?php if (!empty($message['attachments'])): ?>
                    <ul class="attachment-list">
                        <?php foreach ($message['attachments'] as $attachment): ?>
                            <li><a class="button-link" href="/account/tickets/attachments/<?= (int) $attachment['id'] ?>" download><?= htmlspecialchars($attachment['original_name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="ticket-reply" aria-label="Yeni mesaj gönder">
        <h2>Yanıt Yazın</h2>
        <form method="post" enctype="multipart/form-data" class="ticket-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf->token()) ?>">
            <textarea name="message" rows="5" required placeholder="Son durumu aktarın..."></textarea>
            <label for="reply-attachments" class="file-label">Ek dosya ekle (max 2 adet)
                <input type="file" id="reply-attachments" name="attachments[]" multiple accept=".png,.jpg,.jpeg,.pdf,.txt,.zip">
            </label>
            <p class="text-muted" role="note">Toplam 2 MB sınırı uygulanır.</p>
            <button class="button" type="submit">Yanıt Gönder</button>
        </form>
    </section>
</section>
