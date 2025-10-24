<section>
    <h1><?= htmlspecialchars($title) ?></h1>
    <table class="table">
        <thead><tr><th>E-posta</th><th>Rol</th></tr></thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['role']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
