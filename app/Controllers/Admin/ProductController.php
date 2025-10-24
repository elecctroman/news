<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Crypto;
use App\Models\Product;
use App\Models\Category;
use App\Models\Tag;
use App\Models\ProductKey;
use App\Models\Account;

class ProductController extends Controller
{
    public function index(): void
    {
        $csrf = $GLOBALS['csrf'] ?? new Csrf();
        $messages = [];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
                $errors[] = 'Geçersiz güvenlik tokenı.';
            } else {
                $action = $_POST['action'] ?? '';
                if ($action === 'create_product') {
                    $this->handleCreateProduct($messages, $errors);
                } elseif ($action === 'add_variant') {
                    $this->handleAddVariant($messages, $errors);
                } elseif ($action === 'import_keys') {
                    $this->handleImportKeys($messages, $errors);
                } elseif ($action === 'import_accounts') {
                    $this->handleImportAccounts($messages, $errors);
                }
            }
        }

        $products = $this->pdo?->query('SELECT p.*, GROUP_CONCAT(DISTINCT c.name SEPARATOR ", ") AS categories FROM products p LEFT JOIN product_categories pc ON pc.product_id = p.id LEFT JOIN categories c ON c.id = pc.category_id GROUP BY p.id ORDER BY p.created_at DESC LIMIT 50')->fetchAll() ?? [];
        $categories = Category::all($this->pdo);
        $tags = Tag::all($this->pdo);

        $this->view->render('admin/products', [
            'title' => 'Ürün Yönetimi',
            'products' => $products,
            'categories' => $categories,
            'tags' => $tags,
            'messages' => $messages,
            'errors' => $errors,
        ], 'admin');
    }

    private function handleCreateProduct(array &$messages, array &$errors): void
    {
        $name = trim($_POST['name'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        if ($name === '' || $price <= 0) {
            $errors[] = 'Ürün adı ve fiyatı zorunludur.';
            return;
        }

        $slug = $_POST['slug'] ?? $this->slugify($name);
        try {
            $productId = Product::create($this->pdo, [
                'name' => $name,
                'slug' => $slug,
                'short_description' => $_POST['short_description'] ?? null,
                'description' => $_POST['description'] ?? null,
                'base_price' => $price,
                'currency' => $_POST['currency'] ?? 'TRY',
                'type' => $_POST['type'] ?? 'key',
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'is_new' => isset($_POST['is_new']) ? 1 : 0,
            ]);

            $categoryIds = array_map('intval', $_POST['categories'] ?? []);
            $tagIds = array_map('intval', $_POST['tags'] ?? []);
            Product::attachCategories($this->pdo, $productId, $categoryIds);
            Product::attachTags($this->pdo, $productId, $tagIds);

            $messages[] = 'Ürün oluşturuldu.';
        } catch (\Throwable $e) {
            $errors[] = 'Ürün oluşturulamadı: ' . $e->getMessage();
        }
    }

    private function handleAddVariant(array &$messages, array &$errors): void
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        if ($productId <= 0) {
            $errors[] = 'Ürün seçimi zorunludur.';
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO product_variants(product_id, name, region, platform, duration_days, price_adjustment, stock) VALUES(:product_id, :name, :region, :platform, :duration, :adjustment, :stock)');
        $stmt->execute([
            'product_id' => $productId,
            'name' => $_POST['variant_name'] ?? 'Standart',
            'region' => $_POST['region'] ?? null,
            'platform' => $_POST['platform'] ?? null,
            'duration' => $_POST['duration_days'] !== '' ? (int) $_POST['duration_days'] : null,
            'adjustment' => (float) ($_POST['price_adjustment'] ?? 0),
            'stock' => (int) ($_POST['stock'] ?? 0),
        ]);
        $messages[] = 'Varyant eklendi.';
    }

    private function handleImportKeys(array &$messages, array &$errors): void
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $variantId = (int) ($_POST['variant_id'] ?? 0) ?: null;
        $csv = trim($_POST['csv_data'] ?? '');
        if ($productId <= 0 || $csv === '') {
            $errors[] = 'Ürün ve CSV içeriği gereklidir.';
            return;
        }

        try {
            $crypto = new Crypto($this->config['security']['encryption_key']);
            $count = ProductKey::importFromCsv($this->pdo, $crypto, $productId, $variantId, $csv);
            $messages[] = $count . ' anahtar eklendi.';
        } catch (\Throwable $e) {
            $errors[] = 'Anahtar içe aktarılamadı: ' . $e->getMessage();
        }
    }

    private function handleImportAccounts(array &$messages, array &$errors): void
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $variantId = (int) ($_POST['variant_id'] ?? 0) ?: null;
        $csv = trim($_POST['csv_data'] ?? '');
        if ($productId <= 0 || $csv === '') {
            $errors[] = 'Ürün ve CSV içeriği gereklidir.';
            return;
        }

        try {
            $crypto = new Crypto($this->config['security']['encryption_key']);
            $count = Account::importFromCsv($this->pdo, $crypto, $productId, $variantId, $csv);
            $messages[] = $count . ' hesap bilgisi eklendi.';
        } catch (\Throwable $e) {
            $errors[] = 'Hesap içe aktarılamadı: ' . $e->getMessage();
        }
    }

    private function slugify(string $string): string
    {
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        if ($slug === false) {
            $slug = $string;
        }
        $slug = preg_replace('/[^a-zA-Z0-9]+/', '-', $slug);
        $slug = strtolower(trim((string) $slug, '-'));
        return $slug ?: 'urun-' . time();
    }
}
