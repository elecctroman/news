<section>
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php if ($success): ?>
        <div class="alert alert-success">Ayarlar güncellendi.</div>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf->token()) ?>">
        <label for="site_name">Site Adı</label>
        <input id="site_name" name="site_name" value="<?= htmlspecialchars($settings['site_name']) ?>">
        <label for="currency">Para Birimi</label>
        <input id="currency" name="currency" value="<?= htmlspecialchars($settings['currency']) ?>">
        <button class="button" type="submit">Kaydet</button>
    </form>
</section>
