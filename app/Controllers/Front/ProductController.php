<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Product;

class ProductController extends Controller
{
    public function show(string $slug): void
    {
        $product = Product::findBySlug($this->pdo, $slug);
        if ($product === null) {
            http_response_code(404);
            $this->view->render('front/product-not-found', ['title' => 'Ürün bulunamadı']);
            return;
        }

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['name'],
            'description' => strip_tags($product['short_description'] ?? ''),
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => $product['currency'],
                'price' => number_format((float) $product['base_price'], 2, '.', ''),
                'availability' => 'https://schema.org/InStock',
                'url' => $this->config['app']['url'] . '/urun/' . $product['slug'],
            ],
            'category' => array_map(static fn ($cat) => $cat['name'], $product['categories']),
            'url' => $this->config['app']['url'] . '/urun/' . $product['slug'],
        ];

        $this->view->render('front/product', [
            'title' => $product['name'],
            'product' => $product,
            'metaDescription' => $product['meta_description'] ?? $product['short_description'] ?? $product['name'],
            'jsonLd' => $jsonLd,
            'csrf' => $GLOBALS['csrf'] ?? new Csrf(),
        ]);
    }
}
