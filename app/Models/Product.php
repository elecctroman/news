<?php
namespace App\Models;

use PDO;

class Product
{
    public static function create(PDO $pdo, array $data): int
    {
        $stmt = $pdo->prepare('INSERT INTO products(name, slug, short_description, description, base_price, currency, type, status, is_featured, is_new, meta_title, meta_description) VALUES(:name, :slug, :short_description, :description, :base_price, :currency, :type, :status, :is_featured, :is_new, :meta_title, :meta_description)');
        $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'base_price' => $data['base_price'],
            'currency' => $data['currency'] ?? 'TRY',
            'type' => $data['type'] ?? 'key',
            'status' => $data['status'] ?? 'published',
            'is_featured' => $data['is_featured'] ?? 0,
            'is_new' => $data['is_new'] ?? 0,
            'meta_title' => $data['meta_title'] ?? $data['name'],
            'meta_description' => $data['meta_description'] ?? ($data['short_description'] ?? ''),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(PDO $pdo, int $id, array $data): void
    {
        $stmt = $pdo->prepare('UPDATE products SET name = :name, slug = :slug, short_description = :short_description, description = :description, base_price = :base_price, currency = :currency, type = :type, status = :status, is_featured = :is_featured, is_new = :is_new, meta_title = :meta_title, meta_description = :meta_description WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'base_price' => $data['base_price'],
            'currency' => $data['currency'] ?? 'TRY',
            'type' => $data['type'] ?? 'key',
            'status' => $data['status'] ?? 'published',
            'is_featured' => $data['is_featured'] ?? 0,
            'is_new' => $data['is_new'] ?? 0,
            'meta_title' => $data['meta_title'] ?? $data['name'],
            'meta_description' => $data['meta_description'] ?? ($data['short_description'] ?? ''),
        ]);
    }

    public static function attachCategories(PDO $pdo, int $productId, array $categoryIds): void
    {
        $pdo->prepare('DELETE FROM product_categories WHERE product_id = :product_id')->execute(['product_id' => $productId]);
        $stmt = $pdo->prepare('INSERT IGNORE INTO product_categories(product_id, category_id) VALUES(:product_id, :category_id)');
        foreach ($categoryIds as $categoryId) {
            $stmt->execute([
                'product_id' => $productId,
                'category_id' => $categoryId,
            ]);
        }
    }

    public static function attachTags(PDO $pdo, int $productId, array $tagIds): void
    {
        $pdo->prepare('DELETE FROM product_tags WHERE product_id = :product_id')->execute(['product_id' => $productId]);
        $stmt = $pdo->prepare('INSERT IGNORE INTO product_tags(product_id, tag_id) VALUES(:product_id, :tag_id)');
        foreach ($tagIds as $tagId) {
            $stmt->execute([
                'product_id' => $productId,
                'tag_id' => $tagId,
            ]);
        }
    }

    public static function findBySlug(PDO $pdo, string $slug): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE slug = :slug AND status = "published"');
        $stmt->execute(['slug' => $slug]);
        $product = $stmt->fetch();
        if (!$product) {
            return null;
        }

        $product['variants'] = self::variants($pdo, (int) $product['id']);
        $product['categories'] = self::categories($pdo, (int) $product['id']);
        $product['tags'] = self::tags($pdo, (int) $product['id']);

        return $product;
    }

    public static function findById(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();
        if (!$product) {
            return null;
        }

        $product['variants'] = self::variants($pdo, (int) $product['id']);
        return $product;
    }

    public static function featured(PDO $pdo, int $limit = 4): array
    {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE status = "published" AND is_featured = 1 ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function search(PDO $pdo, array $filters): array
    {
        $sql = 'SELECT DISTINCT p.* FROM products p';
        $joins = [];
        $conditions = ['p.status = "published"'];
        $params = [];

        if (!empty($filters['category'])) {
            $joins[] = 'INNER JOIN product_categories pc ON pc.product_id = p.id INNER JOIN categories c ON c.id = pc.category_id';
            $conditions[] = 'c.slug = :category';
            $params['category'] = $filters['category'];
        }

        if (!empty($filters['tag'])) {
            $joins[] = 'INNER JOIN product_tags pt ON pt.product_id = p.id INNER JOIN tags t ON t.id = pt.tag_id';
            $conditions[] = 't.slug = :tag';
            $params['tag'] = $filters['tag'];
        }

        if (!empty($filters['q'])) {
            $conditions[] = '(p.name LIKE :q OR p.short_description LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $sql .= ' ' . implode(' ', array_unique($joins));
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if (!empty($filters['sort']) && $filters['sort'] === 'price_desc') {
            $sql .= ' ORDER BY p.base_price DESC';
        } elseif (!empty($filters['sort']) && $filters['sort'] === 'price_asc') {
            $sql .= ' ORDER BY p.base_price ASC';
        } else {
            $sql .= ' ORDER BY p.created_at DESC';
        }

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();
        $products = $stmt->fetchAll();

        foreach ($products as &$product) {
            $product['variants'] = self::variants($pdo, (int) $product['id']);
        }

        return $products;
    }

    public static function categories(PDO $pdo, int $productId): array
    {
        $stmt = $pdo->prepare('SELECT c.* FROM categories c INNER JOIN product_categories pc ON pc.category_id = c.id WHERE pc.product_id = :id');
        $stmt->execute(['id' => $productId]);
        return $stmt->fetchAll();
    }

    public static function tags(PDO $pdo, int $productId): array
    {
        $stmt = $pdo->prepare('SELECT t.* FROM tags t INNER JOIN product_tags pt ON pt.tag_id = t.id WHERE pt.product_id = :id');
        $stmt->execute(['id' => $productId]);
        return $stmt->fetchAll();
    }

    public static function variants(PDO $pdo, int $productId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM product_variants WHERE product_id = :id ORDER BY price_adjustment ASC');
        $stmt->execute(['id' => $productId]);
        return $stmt->fetchAll();
    }
}
