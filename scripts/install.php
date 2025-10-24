<?php
declare(strict_types=1);

use App\Core\Crypto;

spl_autoload_register(static function (string $class): void {
    $baseDir = dirname(__DIR__);
    $path = $baseDir . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    $mailFrom = trim($_POST['mail_from'] ?? '');
    $appUrl = trim($_POST['app_url'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? 'admin@example.com');
    $adminPass = $_POST['admin_pass'] ?? 'Admin123!';

    if ($dbHost === '' || $dbName === '' || $dbUser === '' || $mailFrom === '' || $appUrl === '') {
        $errors[] = 'Lütfen tüm alanları doldurun.';
    } else {
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbName);
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $schema = file_get_contents(__DIR__ . '/schema.sql');
            $pdo->exec($schema);

            $pdo->exec("INSERT INTO roles(name) VALUES('super_admin') ON DUPLICATE KEY UPDATE name=name");
            $pdo->exec("INSERT INTO roles(name) VALUES('operator') ON DUPLICATE KEY UPDATE name=name");
            $pdo->exec("INSERT INTO roles(name) VALUES('customer') ON DUPLICATE KEY UPDATE name=name");

            $rolePermissions = [
                'super_admin' => ['access_admin', 'manage_users', 'manage_products', 'view_reports'],
                'operator' => ['access_admin', 'manage_products'],
            ];

            foreach ($rolePermissions as $roleName => $permissions) {
                $roleId = (int) $pdo->query("SELECT id FROM roles WHERE name = '" . $roleName . "'")->fetchColumn();
                foreach ($permissions as $permission) {
                    $stmt = $pdo->prepare('INSERT INTO role_permissions(role_id, permission) VALUES(:role_id, :permission) ON DUPLICATE KEY UPDATE permission=permission');
                    $stmt->execute([
                        'role_id' => $roleId,
                        'permission' => $permission,
                    ]);
                }
            }

            $adminRoleId = (int) $pdo->query("SELECT id FROM roles WHERE name='super_admin'")->fetchColumn();
            $customerRoleId = (int) $pdo->query("SELECT id FROM roles WHERE name='customer'")->fetchColumn();
            $adminPassword = password_hash($adminPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users(email, password, role_id, name, email_verified_at) VALUES(:email, :password, :role_id, :name, NOW()) ON DUPLICATE KEY UPDATE password=VALUES(password)');
            $stmt->execute([
                'email' => $adminEmail,
                'password' => $adminPassword,
                'role_id' => $adminRoleId,
                'name' => 'Kurucu Yönetici',
            ]);
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $adminEmail]);
            $adminUserId = (int) $stmt->fetchColumn();

            $customerStmt = $pdo->prepare('INSERT INTO users(email, password, role_id, name, email_verified_at) VALUES(:email, :password, :role_id, :name, NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name)');
            $customerStmt->execute([
                'email' => 'customer@example.com',
                'password' => password_hash('Customer123!', PASSWORD_DEFAULT),
                'role_id' => $customerRoleId,
                'name' => 'Demo Müşteri',
            ]);
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => 'customer@example.com']);
            $demoCustomerId = (int) $stmt->fetchColumn();

            $encryptionKey = random_bytes(32);
            $env = [
                'APP_NAME' => 'Dijital Mağaza',
                'APP_URL' => $appUrl,
                'DB_HOST' => $dbHost,
                'DB_NAME' => $dbName,
                'DB_USER' => $dbUser,
                'DB_PASS' => $dbPass,
                'MAIL_FROM' => $mailFrom,
                'ENCRYPTION_KEY' => 'base64:' . base64_encode($encryptionKey),
            ];

            $crypto = new Crypto($encryptionKey);

            $categories = [
                ['name' => 'Oyun E-PIN', 'slug' => 'oyun-epin'],
                ['name' => 'Abonelik', 'slug' => 'abonelik'],
                ['name' => 'Hesaplar', 'slug' => 'hesaplar'],
            ];

            foreach ($categories as $category) {
                $stmt = $pdo->prepare('INSERT INTO categories(name, slug, meta_title, meta_description) VALUES(:name, :slug, :meta_title, :meta_description) ON DUPLICATE KEY UPDATE name=VALUES(name)');
                $stmt->execute([
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'meta_title' => $category['name'] . ' - Mağaza',
                    'meta_description' => $category['name'] . ' kategorisindeki dijital ürünler.',
                ]);
            }

            $tags = [
                ['name' => 'Steam', 'slug' => 'steam'],
                ['name' => 'PlayStation', 'slug' => 'playstation'],
                ['name' => 'Netflix', 'slug' => 'netflix'],
            ];
            foreach ($tags as $tag) {
                $stmt = $pdo->prepare('INSERT INTO tags(name, slug) VALUES(:name, :slug) ON DUPLICATE KEY UPDATE name=VALUES(name)');
                $stmt->execute($tag);
            }

            $ticketTags = [
                ['name' => 'Öncelikli', 'slug' => 'priority', 'color' => '#dc2626'],
                ['name' => 'İade Talebi', 'slug' => 'refund', 'color' => '#0ea5e9'],
                ['name' => 'Fraud İnceleme', 'slug' => 'fraud', 'color' => '#f59e0b'],
            ];
            foreach ($ticketTags as $tag) {
                $stmt = $pdo->prepare('INSERT INTO ticket_tags(name, slug, color) VALUES(:name, :slug, :color) ON DUPLICATE KEY UPDATE name=VALUES(name), color=VALUES(color)');
                $stmt->execute($tag);
            }

            $macros = [
                ['title' => 'Sipariş Onayı', 'body' => "Merhaba,\nSiparişiniz başarıyla onaylandı ve teslimat birkaç dakika içinde tamamlanacaktır. Yardıma ihtiyacınız olursa yanıtlayabilirsiniz."],
                ['title' => 'İade Talimatı', 'body' => "Merhaba,\nİade talebinizi aldık. Lütfen hesabınızı güvenli şekilde kapatıp bu mesajı yanıtlayın, iade sürecini başlatalım."],
            ];
            foreach ($macros as $macro) {
                $stmt = $pdo->prepare('INSERT INTO ticket_macros(title, body) VALUES(:title, :body) ON DUPLICATE KEY UPDATE body=VALUES(body)');
                $stmt->execute($macro);
            }

            $products = [
                [
                    'name' => 'Steam Cüzdan Kodu 50 TL',
                    'slug' => 'steam-cuzdan-50',
                    'short_description' => 'Steam cüzdan bakiyenizi artırın.',
                    'description' => '<p>Steam hesabınıza hızlıca bakiye ekleyin.</p>',
                    'base_price' => 50.00,
                    'currency' => 'TRY',
                    'type' => 'key',
                    'is_featured' => 1,
                    'category_slug' => 'oyun-epin',
                    'tags' => ['steam'],
                    'variants' => [
                        ['name' => 'Global', 'region' => 'Global', 'platform' => 'Steam', 'price_adjustment' => 0, 'stock' => 10],
                    ],
                    'keys' => ['ABCD-EFGH-IJKL-MNOP', 'QRST-UVWX-YZ12-3456'],
                ],
                [
                    'name' => 'Netflix Premium 1 Ay',
                    'slug' => 'netflix-premium-1-ay',
                    'short_description' => 'Premium plan erişimi 1 ay.',
                    'description' => '<p>Ultra HD Netflix deneyimi.</p>',
                    'base_price' => 129.90,
                    'currency' => 'TRY',
                    'type' => 'account',
                    'is_new' => 1,
                    'category_slug' => 'abonelik',
                    'tags' => ['netflix'],
                    'variants' => [
                        ['name' => 'TR Bölgesi', 'region' => 'TR', 'platform' => 'Web', 'price_adjustment' => 0, 'stock' => 5],
                    ],
                    'accounts' => [
                        ['email' => 'sample1@example.com', 'password' => 'Pass1234', 'note' => 'Profil 1'],
                        ['email' => 'sample2@example.com', 'password' => 'Pass5678', 'note' => 'Profil 2'],
                    ],
                ],
            ];

            foreach ($products as $product) {
                $stmt = $pdo->prepare('INSERT INTO products(name, slug, short_description, description, base_price, currency, type, is_featured, is_new, meta_title, meta_description) VALUES(:name, :slug, :short, :description, :price, :currency, :type, :featured, :new, :meta_title, :meta_description) ON DUPLICATE KEY UPDATE name=VALUES(name)');
                $stmt->execute([
                    'name' => $product['name'],
                    'slug' => $product['slug'],
                    'short' => $product['short_description'],
                    'description' => $product['description'],
                    'price' => $product['base_price'],
                    'currency' => $product['currency'],
                    'type' => $product['type'],
                    'featured' => $product['is_featured'] ?? 0,
                    'new' => $product['is_new'] ?? 0,
                    'meta_title' => $product['name'],
                    'meta_description' => $product['short_description'],
                ]);
                $productId = (int) $pdo->lastInsertId();

                $catStmt = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug');
                $catStmt->execute(['slug' => $product['category_slug']]);
                $categoryId = (int) $catStmt->fetchColumn();
                if ($categoryId > 0) {
                    $stmt = $pdo->prepare('INSERT IGNORE INTO product_categories(product_id, category_id) VALUES(:product_id, :category_id)');
                    $stmt->execute([
                        'product_id' => $productId,
                        'category_id' => $categoryId,
                    ]);
                }

                foreach ($product['tags'] as $tagSlug) {
                    $tagStmt = $pdo->prepare('SELECT id FROM tags WHERE slug = :slug');
                    $tagStmt->execute(['slug' => $tagSlug]);
                    $tagId = $tagStmt->fetchColumn();
                    if ($tagId) {
                        $stmt = $pdo->prepare('INSERT IGNORE INTO product_tags(product_id, tag_id) VALUES(:product_id, :tag_id)');
                        $stmt->execute([
                            'product_id' => $productId,
                            'tag_id' => $tagId,
                        ]);
                    }
                }

                $variantMap = [];
                foreach ($product['variants'] as $variant) {
                    $stmt = $pdo->prepare('INSERT INTO product_variants(product_id, name, region, platform, duration_days, price_adjustment, stock) VALUES(:product_id, :name, :region, :platform, :duration, :adjustment, :stock)');
                    $stmt->execute([
                        'product_id' => $productId,
                        'name' => $variant['name'],
                        'region' => $variant['region'],
                        'platform' => $variant['platform'],
                        'duration' => $variant['duration_days'] ?? null,
                        'adjustment' => $variant['price_adjustment'],
                        'stock' => $variant['stock'],
                    ]);
                    $variantMap[$variant['name']] = (int) $pdo->lastInsertId();
                }

                if (!empty($product['keys'])) {
                    foreach ($product['keys'] as $key) {
                        $payload = $crypto->encrypt($key);
                        $stmt = $pdo->prepare('INSERT INTO product_keys(product_id, variant_id, encrypted_key, iv, tag) VALUES(:product_id, :variant_id, :encrypted_key, :iv, :tag)');
                        $stmt->execute([
                            'product_id' => $productId,
                            'variant_id' => $variantMap['Global'] ?? null,
                            'encrypted_key' => $payload['ciphertext'],
                            'iv' => $payload['iv'],
                            'tag' => $payload['tag'],
                        ]);
                    }
                }

                if (!empty($product['accounts'])) {
                    foreach ($product['accounts'] as $account) {
                        $data = json_encode($account, JSON_THROW_ON_ERROR);
                        $payload = $crypto->encrypt($data);
                        $stmt = $pdo->prepare('INSERT INTO accounts(product_id, variant_id, encrypted_payload, iv, tag) VALUES(:product_id, :variant_id, :payload, :iv, :tag)');
                        $stmt->execute([
                            'product_id' => $productId,
                            'variant_id' => $variantMap['TR Bölgesi'] ?? null,
                            'payload' => $payload['ciphertext'],
                            'iv' => $payload['iv'],
                            'tag' => $payload['tag'],
                        ]);
                    }
                }
            }

            $pdo->prepare('INSERT INTO coupons(code, type, value, usage_limit, min_total, status) VALUES(:code, :type, :value, :limit, :min_total, :status) ON DUPLICATE KEY UPDATE type=VALUES(type), value=VALUES(value), usage_limit=VALUES(usage_limit), min_total=VALUES(min_total), status=VALUES(status)')->execute([
                'code' => 'HOSGELDIN10',
                'type' => 'percent',
                'value' => 10,
                'limit' => 100,
                'min_total' => 50,
                'status' => 'active',
            ]);

            $stmt = $pdo->prepare('INSERT INTO settings(setting_key, setting_value) VALUES(:key, :value) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
            $stmt->execute([
                'key' => 'site_name',
                'value' => 'Dijital Mağaza İskeleti',
            ]);
            $stmt->execute([
                'key' => 'default_currency',
                'value' => 'TRY',
            ]);
            $stmt->execute([
                'key' => 'mail_smtp_host',
                'value' => 'smtp.example.com',
            ]);
            $stmt->execute([
                'key' => 'payment_default_gateway',
                'value' => 'mock',
            ]);
            $stmt->execute([
                'key' => 'maintenance_mode',
                'value' => 'off',
            ]);
            $stmt->execute([
                'key' => 'maintenance_whitelist',
                'value' => '127.0.0.1',
            ]);

            $ticketStmt = $pdo->prepare('INSERT INTO tickets(user_id, subject, status, priority) VALUES(:user_id, :subject, :status, :priority)');
            $ticketStmt->execute([
                'user_id' => $demoCustomerId ?: null,
                'subject' => 'Örnek destek talebi',
                'status' => 'waiting_customer',
                'priority' => 'normal',
            ]);
            $sampleTicketId = (int) $pdo->lastInsertId();
            if ($sampleTicketId > 0) {
                $messageStmt = $pdo->prepare('INSERT INTO ticket_messages(ticket_id, user_id, sender_type, message) VALUES(:ticket_id, :user_id, :sender_type, :message)');
                $messageStmt->execute([
                    'ticket_id' => $sampleTicketId,
                    'user_id' => $demoCustomerId ?: null,
                    'sender_type' => 'customer',
                    'message' => 'Merhaba, örnek destek talebi mesajıdır. Yanıt verdiğinizde burada görünecek.',
                ]);
                $messageStmt->execute([
                    'ticket_id' => $sampleTicketId,
                    'user_id' => $adminUserId ?: null,
                    'sender_type' => 'admin',
                    'message' => 'Destek ekibi yanıtı örneği: Sorununuzu takip ediyoruz.',
                ]);
            }

            $envContent = "<?php\nreturn " . var_export($env, true) . ";\n";
            file_put_contents(dirname(__DIR__) . '/config/.env.php', $envContent);

            $success = true;
        } catch (\Throwable $e) {
            $errors[] = 'Kurulum sırasında hata oluştu: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Kurulum Sihirbazı</title>
    <link rel="stylesheet" href="/assets/css/app.min.css">
</head>
<body>
<main class="card">
    <h1>Kurulum Sihirbazı</h1>
    <?php if ($success): ?>
        <div class="alert alert-success" role="alert">
            Kurulum tamamlandı. <a href="/">Siteye dön</a>
        </div>
    <?php else: ?>
        <?php if ($errors): ?>
            <div class="alert alert-error" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" novalidate>
            <label>Uygulama URL
                <input name="app_url" required value="<?= htmlspecialchars($_POST['app_url'] ?? 'http://localhost', ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label>DB Host
                <input name="db_host" required value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost', ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label>DB Adı
                <input name="db_name" required value="<?= htmlspecialchars($_POST['db_name'] ?? 'epin', ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label>DB Kullanıcı
                <input name="db_user" required value="<?= htmlspecialchars($_POST['db_user'] ?? 'root', ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label>DB Şifre
                <input name="db_pass" type="password" value="<?= htmlspecialchars($_POST['db_pass'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label>E-posta Gönderen
                <input name="mail_from" required value="<?= htmlspecialchars($_POST['mail_from'] ?? 'no-reply@example.com', ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label>Yönetici E-posta
                <input name="admin_email" required value="<?= htmlspecialchars($_POST['admin_email'] ?? 'admin@example.com', ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label>Yönetici Parola
                <input name="admin_pass" type="password" value="<?= htmlspecialchars($_POST['admin_pass'] ?? 'Admin123!', ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <button class="button" type="submit">Kurulumu Tamamla</button>
        </form>
        <p class="text-muted">Kurulum örnek veriler, kategoriler ve şifrelenmiş stok ekler.</p>
    <?php endif; ?>
</main>
</body>
</html>
