<section class="payment-mock">
    <h1><?= htmlspecialchars($title) ?></h1>
    <p>Test ödeme ekranı. Tutar: <?= htmlspecialchars($amount) ?> <?= htmlspecialchars($currency) ?></p>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->token()) ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <fieldset>
            <legend>Sonuç</legend>
            <label><input type="radio" name="result" value="success" checked> Başarılı</label>
            <label><input type="radio" name="result" value="failed"> Başarısız</label>
        </fieldset>
        <button class="button" type="submit">Ödemeyi Tamamla</button>
    </form>
</section>
