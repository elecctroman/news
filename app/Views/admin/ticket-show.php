<section class="ticket-detail">
    <header class="ticket-detail__header">
        <?php $categoryLabels = ['support' => 'Genel', 'order' => 'Sipariş', 'payment' => 'Ödeme', 'dispute' => 'Uyuşmazlık']; ?>
        <div>
            <h1><?= htmlspecialchars($title) ?></h1>
            <p class="text-muted">Kullanıcı: <?= htmlspecialchars($ticket['email'] ?? 'Bilinmiyor') ?> · Durum: <span class="status status-<?= htmlspecialchars($ticket['status']) ?>"><?= htmlspecialchars($ticket['status']) ?></span> · Öncelik: <?= htmlspecialchars($ticket['priority']) ?> · Kategori: <?= htmlspecialchars($categoryLabels[$ticket['category']] ?? $ticket['category']) ?></p>
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
            <input type="hidden" name="action" value="status">
            <label for="status" class="visually-hidden">Durum değiştir</label>
            <select id="status" name="status">
                <?php foreach (['open' => 'Açık','waiting_admin' => 'Müşteri Yanıtı Bekleniyor','waiting_customer' => 'Müşteri Bekleniyor','dispute' => 'Uyuşmazlık','resolved' => 'Çözüldü','closed' => 'Kapalı'] as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= $ticket['status'] === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button-small" type="submit">Güncelle</button>
        </form>
    </header>
    <div class="ticket-metadata">
        <form method="post" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf->token()) ?>">
            <input type="hidden" name="action" value="priority">
            <label for="priority" class="visually-hidden">Öncelik</label>
            <select id="priority" name="priority">
                <option value="normal" <?= $ticket['priority'] === 'normal' ? 'selected' : '' ?>>Normal</option>
                <option value="urgent" <?= $ticket['priority'] === 'urgent' ? 'selected' : '' ?>>Acil</option>
            </select>
            <button class="button-small" type="submit">Öncelik Kaydet</button>
        </form>
        <form method="post" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf->token()) ?>">
            <input type="hidden" name="action" value="category">
            <label for="category" class="visually-hidden">Kategori</label>
            <select id="category" name="category">
                <?php foreach ($categories as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= $ticket['category'] === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button-small" type="submit">Kategori Kaydet</button>
        </form>
        <form method="post" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf->token()) ?>">
            <input type="hidden" name="action" value="tags">
            <fieldset>
                <legend class="visually-hidden">Etiketler</legend>
                <?php foreach ($allTags as $tag): ?>
                    <?php $checked = false; foreach ($ticket['tags'] as $assigned) { if ((int)$assigned['id'] === (int)$tag['id']) { $checked = true; break; } } ?>
                    <label class="tag-checkbox">
                        <input type="checkbox" name="tags[]" value="<?= (int) $tag['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                        <span>#<?= htmlspecialchars($tag['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <button class="button-small" type="submit">Etiketleri Güncelle</button>
        </form>
    </div>

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
        <div class="alert alert-success">Değişiklik kaydedildi.</div>
    <?php endif; ?>

    <section class="ticket-thread" aria-label="Destek mesajları">
        <?php foreach ($ticket['messages'] as $message): ?>
            <article class="ticket-message ticket-message--<?= htmlspecialchars($message['sender_type']) ?>">
                <header>
                    <strong><?= htmlspecialchars($message['sender_type'] === 'admin' ? 'Destek Ekibi' : ($ticket['email'] ?? 'Kullanıcı')) ?></strong>
                    <span class="text-muted"><?= htmlspecialchars($message['created_at']) ?></span>
                </header>
                <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                <?php if (!empty($message['attachments'])): ?>
                    <ul class="attachment-list">
                        <?php foreach ($message['attachments'] as $attachment): ?>
                            <li><a class="button-link" href="/admin/tickets/attachments/<?= (int) $attachment['id'] ?>" download><?= htmlspecialchars($attachment['original_name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="ticket-reply" aria-label="Yanıtla">
        <h2>Yanıt Gönder</h2>
        <form method="post" enctype="multipart/form-data" class="ticket-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf->token()) ?>">
            <label for="macro_id">Hazır Yanıt Kullan</label>
            <select id="macro_id" name="macro_id" data-macro-select>
                <option value="">Makro Seçin</option>
                <?php foreach ($macros as $macro): ?>
                    <option value="<?= (int) $macro['id'] ?>" data-body="<?= htmlspecialchars($macro['body']) ?>"><?= htmlspecialchars($macro['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <textarea name="message" rows="5" required placeholder="Müşteriye yanıtınız..."></textarea>
            <label for="admin-attachments" class="file-label">Ek dosya ekle
                <input type="file" id="admin-attachments" name="attachments[]" multiple accept=".png,.jpg,.jpeg,.pdf,.txt,.zip">
            </label>
            <p class="text-muted" role="note">En fazla 4 dosya, 4 MB sınırı.</p>
            <button class="button" type="submit">Yanıt Gönder</button>
        </form>
    </section>
</section>
